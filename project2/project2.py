
import os
import random
from collections import Counter
from PIL import Image, ImageDraw
import numpy as np
import json
from io import BytesIO
from experta import *
import arabic_reshaper
from bidi.algorithm import get_display
import uuid
from flask import Flask, request, jsonify, url_for, send_from_directory
import requests






app = Flask(__name__)
#/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

# توليد الأحرف المستهدفة
def generate_target_letters(count):
    return random.sample([chr(i) for i in range(65, 91)], count)

# توليد شبكة الأحرف
def generate_letter_grid(count, target_letters):
    sizes = {3:(6,6), 5:(8,8), 6:(10,10)}
    rows, cols = sizes.get(count, (10,10))
    total_cells = rows * cols
    target_repeats_total = total_cells // 2
    per_letter_repeat = target_repeats_total // len(target_letters)

    letters_list = []
    for letter in target_letters:
        letters_list.extend([letter] * per_letter_repeat)

    remaining = total_cells - len(letters_list)
    distractors = [chr(i) for i in range(65, 91) if chr(i) not in target_letters]
    letters_list.extend(random.choices(distractors, k=remaining))

    random.shuffle(letters_list)
    grid = [letters_list[i*cols:(i+1)*cols] for i in range(rows)]
    return grid

# تقييم نتائج الطفل
def evaluate_cancellations_with_counts(target_letters, cancelled_letters):
    target_counts = Counter(target_letters)
    cancelled_counts = Counter(cancelled_letters)

    correct = 0
    wrong = 0
    missed = 0

    for letter, count in target_counts.items():
        cancelled_count = cancelled_counts.get(letter, 0)
        correct += min(count, cancelled_count)
        missed += max(0, count - cancelled_count)

    for letter, count in cancelled_counts.items():
        if letter not in target_counts:
            wrong += count

    return correct, wrong, missed

# تحليل الأداء
def analyze_slct_two_rounds(total_letters_1, wrong_1, time_1,
                            total_letters_2, wrong_2, time_2,
                            count, gender):
    norms = {
        "male": {
            3: {"mean": 20.5, "std": 4.5},
            5: {"mean": 23.0, "std": 5.0},
            6: {"mean": 25.5, "std": 5.5}
        },
        "female": {
            3: {"mean": 22.0, "std": 4.0},
            5: {"mean": 24.5, "std": 4.5},
            6: {"mean": 27.0, "std": 5.0}
        }
    }

    if gender not in norms or count not in norms[gender]:
        return {"error": "Unsupported age or gender"}

    ref = norms[gender][count]

    def evaluate_round(total, wrong, time):
        net_score = total - wrong
        speed = net_score / time if time > 0 else 0
        z = (net_score - ref["mean"]) / ref["std"]
        if z >= 1.0:
            level = "Above Average"
        elif z >= -1.0:
            level = "Average"
        else:
            level = "Below Average"
        return {
            "Net Score": net_score,
            "Z Score": round(z, 2),
            "Speed (net items/sec)": round(speed, 3),
            "Performance Level": level
        }

    round1 = evaluate_round(total_letters_1, wrong_1, time_1)
    round2 = evaluate_round(total_letters_2, wrong_2, time_2)

    def compare(val1, val2):
        if val2 > val1:
            return "Improved"
        elif val2 < val1:
            return "Declined"
        else:
            return "No Change"

    comparison = {
        "Z Score Change": compare(round1["Z Score"], round2["Z Score"]),
        "Net Score Change": compare(round1["Net Score"], round2["Net Score"]),
        "Speed Change": compare(round1["Speed (net items/sec)"], round2["Speed (net items/sec)"])
    }

    return {
        "Round 1": round1,
        "Round 2": round2,
        "Performance Comparison": comparison
    }

# مسار API لإنشاء التحدي
@app.route('/api/slct/generate', methods=['POST'])
def generate():
    data = request.get_json()
    count = data.get("count")  # عدد الحروف المستهدفة (حسب المستوى)
    if not count:
        return jsonify({"error": "Missing 'count'"}), 400

    target_letters = generate_target_letters(count)
    grid = generate_letter_grid(count, target_letters)

    return jsonify({
        "target_letters": target_letters,
        "grid": grid
    })

# مسار لتقييم الأداء
@app.route('/api/slct/evaluate', methods=['POST'])
def evaluate():
    data = request.get_json()

    target_letters = data.get("target_letters", [])
    cancelled_letters_1 = data.get("cancelled_letters_1", [])
    cancelled_letters_2 = data.get("cancelled_letters_2", [])
    time1 = data.get("time1", 60)
    time2 = data.get("time2", 60)
    gender = data.get("gender")
    count = data.get("count")

    if not target_letters or not cancelled_letters_1 or not cancelled_letters_2 or not gender or not count:
        return jsonify({"error": "Missing required fields"}), 400

    # توليد التكرارات الكافية من الحروف المستهدفة (نفس الحساب المستخدم في الشبكة)
    estimated_grid_size = max(len(cancelled_letters_1), len(cancelled_letters_2)) * 2
    repeated_targets = target_letters * (estimated_grid_size // len(target_letters))

    # تقييم الجولة الأولى
    correct1, wrong1, missed1 = evaluate_cancellations_with_counts(repeated_targets, cancelled_letters_1)
    total_letters_1 = correct1 + missed1
    wrong_total_1 = wrong1 + missed1

    # تقييم الجولة الثانية
    correct2, wrong2, missed2 = evaluate_cancellations_with_counts(repeated_targets, cancelled_letters_2)
    total_letters_2 = correct2 + missed2
    wrong_total_2 = wrong2 + missed2

    # تحليل الأداء بناءً على كل جولة
    analysis = analyze_slct_two_rounds(
        total_letters_1, wrong_total_1, time1,
        total_letters_2, wrong_total_2, time2,
        count, gender
    )

    return jsonify({
        "round_1": {
            "correct": correct1,
            "wrong": wrong1,
            "missed": missed1,
            "time": time1
        },
        "round_2": {
            "correct": correct2,
            "wrong": wrong2,
            "missed": missed2,
            "time": time2
        },
        "analysis": analysis
    })
#////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
COLAB_URL = "https://bb824add7c2f.ngrok-free.app/transcribe"  #<--------------------
@app.route("/analyzeVoice", methods=["POST"])
def analyze():
    if 'audio' not in request.files:    
        return jsonify({'error': 'الملف الصوتي مفقود'}), 400

    audio = request.files['audio']
    temp_path = "temp_audio.mp3"
    audio.save(temp_path)

    with open(temp_path, 'rb') as f:
        files = {'audio': ('temp_audio.mp3', f, 'audio/mpeg')}   
        colab_response = requests.post(COLAB_URL, files=files)

    os.remove(temp_path)

    print("🔎 Colab Status:", colab_response.status_code)
    print("📄 Colab Response:", colab_response.text)

    if colab_response.status_code != 200:
        return jsonify({'error': 'فشل من Colab'}), 500

    return jsonify({'text': colab_response.json().get("text", "")})  
#////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
STANDARDS = {
    4: {"targets": 1, "total": 4, "max_time": 20},
    5: {"targets": 2, "total": 5, "max_time": 18},
    6: {"targets": 3, "total": 6, "max_time": 15},
}


def analyze_mot(age, displayed_ids, selected_ids, target_ids, response_time):
    if age not in STANDARDS:
        return {"error": "Unsupported age"}

    std = STANDARDS[age]

    if len(displayed_ids) != std["total"]:
        return {"error": f"Expected {std['total']} displayed items, got {len(displayed_ids)}"}

    if len(target_ids) != std["targets"]:
        return {"error": f"Expected {std['targets']} target(s), got {len(target_ids)}"}

    if len(selected_ids) > std["targets"]:
        return {"error": f"Too many selections. Allowed: {std['targets']}, got: {len(selected_ids)}"}

    score = 0
    used_target_indexes = set()

    for selected_id in selected_ids:
        if selected_id not in displayed_ids:
            continue

        selected_index = displayed_ids.index(selected_id)
        found_exact = False

        for i, target_id in enumerate(target_ids):
            if i in used_target_indexes or target_id not in displayed_ids:
                continue

            target_index = displayed_ids.index(target_id)

            if abs(selected_index - target_index) == 0:
                score += 1
                used_target_indexes.add(i)
                found_exact = True
                break

        if not found_exact:
            for i, target_id in enumerate(target_ids):
                if i in used_target_indexes or target_id not in displayed_ids:
                    continue

                target_index = displayed_ids.index(target_id)
                if abs(selected_index - target_index) == 1:
                    score += 0.5
                    used_target_indexes.add(i)
                    break

    accuracy = (score / std["targets"]) * 100

    if accuracy == 100:
        acc_status = "Excellent"
    elif accuracy >= 66:
        acc_status = "Good"
    else:
        acc_status = "Weak"

    time_status = "Acceptable" if response_time <= std["max_time"] else "Slow"

    if acc_status in ["Excellent", "Good"] and time_status == "Acceptable":
        result = "Within normal range"
    elif acc_status == "Weak" and time_status == "Slow":
        result = "Delayed in both accuracy and response time"
    elif acc_status == "Weak":
        result = "Low accuracy"
    elif time_status == "Slow":
        result = "Slow response time"

    return {
        "correct score": round(score, 2),
        "accuracy (%)": round(accuracy, 2),
        "status_accuracy": acc_status,
        "response_time/(s)": response_time,
        "time_status": time_status,
        "final_classification": result
    }


@app.route("/api/mot/evaluate", methods=["POST"])
def evaluate_mot():
    data = request.get_json()

    age = data.get("age")
    displayed_ids = data.get("displayed_ids")
    selected_ids = data.get("selected_ids")
    target_ids = data.get("target_ids")
    response_time = data.get("response_time")

    if not all([isinstance(displayed_ids, list), isinstance(selected_ids, list),
                isinstance(target_ids, list), isinstance(response_time, (int, float)), isinstance(age, int)]):
        return jsonify({"error": "Invalid input"}), 400

    result = analyze_mot(age, displayed_ids, selected_ids, target_ids, response_time)
    return jsonify({"analysis": result})

#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
def find_difference_regions(img1_path, img2_path, threshold=30, min_cluster_size=25):
    img1 = Image.open(img1_path).convert('RGB')
    img2 = Image.open(img2_path).convert('RGB')

    if img1.size != img2.size:
        raise ValueError("Images must have the same dimensions")

    arr1 = np.array(img1)
    arr2 = np.array(img2)
    diff = np.abs(arr1.astype(int) - arr2.astype(int))
    diff_sum = np.sum(diff, axis=2)
    diff_coords = np.argwhere(diff_sum > threshold)

    visited = np.zeros(diff_sum.shape, dtype=bool)
    regions = []

    def bfs(start):
        queue = [start]
        cluster = []

        while queue:
            x, y = queue.pop()
            if visited[x, y]:
                continue
            visited[x, y] = True
            cluster.append((x, y))

            for dx in [-1, 0, 1]:
                for dy in [-1, 0, 1]:
                    nx, ny = x + dx, y + dy
                    if 0 <= nx < visited.shape[0] and 0 <= ny < visited.shape[1]:
                        if not visited[nx, ny] and diff_sum[nx, ny] > threshold:
                            queue.append((nx, ny))
        return cluster

    for x, y in diff_coords:
        if not visited[x, y]:
            cluster = bfs((x, y))
            if len(cluster) >= min_cluster_size:
                xs = [pt[1] for pt in cluster]
                ys = [pt[0] for pt in cluster]
                x_min, x_max = min(xs), max(xs)
                y_min, y_max = min(ys), max(ys)
                regions.append((x_min, y_min, x_max, y_max))

    return regions

def check_spots_vs_regions(spots_pressed, regions, times):
    total = len(regions)
    found = 0
    wrong = 0
    correct_times = []

    for spot, time in zip(spots_pressed, times):
        x, y = spot
        matched = False
        for rx1, ry1, rx2, ry2 in regions:
            if rx1 <= x <= rx2 and ry1 <= y <= ry2:
                found += 1
                correct_times.append(time)
                matched = True
                break
        if not matched:
            wrong += 1

    misses = total - found
    return found, misses, wrong, total, correct_times

def analyze_spot_diff(found, total, wrong, misses, times, age):
    norms = {
        4: {4: {"acc_mean": 0.793, "acc_sd": 0.05,  "rt_mean": 1.908, "rt_sd": 0.02, "err_mean": 0.29, "err_sd": 0.12}},
        5: {4: {"acc_mean": 0.923, "acc_sd": 0.012, "rt_mean": 1.463, "rt_sd": 0.039, "err_mean": 0.08, "err_sd": 0.02}},
        6: {4: {"acc_mean": 0.957, "acc_sd": 0.013, "rt_mean": 1.238, "rt_sd": 0.036, "err_mean": 0.05, "err_sd": 0.015}},
    }

    if age not in norms or total not in norms[age]:
        return {"error": "No normative data for this age and set size."}

    error = misses + wrong
    acc = found / total if total > 0 else 0
    err = error / (found + error) if (found + error) > 0 else 0
    valid_times = [int(t) for t in times if 200 <= int(t) <= 9000]
    rt_ms = sum(valid_times) / len(valid_times) if valid_times else 0
    rt = rt_ms / 1000
    ref = norms[age][total]

    def z_score(value, mean, std):
        return (value - mean) / std if std != 0 else 0

    def interpret(z, higher_good=True):
        if higher_good:
            if z < -1:
                return "Below average --Needs Support"
            elif z > 1:
                return "Above average --Excellent"
            else:
                return "Within normal range "
        else:
            if z < -1:
                return "Below average --Excellent"
            elif z > 1:
                return "Above average --Needs Support"
            else:
                return "Within normal range "

    z_acc = z_score(acc, ref["acc_mean"], ref["acc_sd"])
    z_rt = z_score(rt, ref["rt_mean"], ref["rt_sd"])
    z_err = z_score(err, ref["err_mean"], ref["err_sd"])

    results = {
        "Accuracy": {
            "Value": round(acc, 2),
            "z_acc": round(z_acc, 2),
            "Assessment": interpret(z_acc, True)
        },
        "Speed": {
            "Value": round(rt, 3),
            "z_rt": round(z_rt, 2),
            "Assessment": interpret(z_rt, False)
        },
        "Error Rate": {
            "Value": round(err, 2),
            "z_err": round(z_err, 2),
            "Assessment": interpret(z_err, False)
        }
    }

    return results


def convert_regions_to_list(regions):
    return [[int(x_min), int(y_min), int(x_max), int(y_max)] for (x_min, y_min, x_max, y_max) in regions]

@app.route('/api/difference/evaluate', methods=['POST'])
def evaluate_difference():
    try:
        # استقبال الصور
        img1 = request.files.get('img1')
        img2 = request.files.get('img2')

        if not img1 or not img2:
            return jsonify({'error': 'Missing images'}), 400

        # استقبال البيانات
        age = int(request.form['age'])
        spots_pressed = json.loads(request.form['spots_pressed'])
        times = json.loads(request.form['times'])

        # حفظ الصور مؤقتًا في مجلد مؤقت
        img1_path = f'temp_img1_{img1.filename}'
        img2_path = f'temp_img2_{img2.filename}'
        img1.save(img1_path)
        img2.save(img2_path)

        # إيجاد مناطق الاختلاف
        regions = find_difference_regions(img1_path, img2_path)

        # التحقق من الضغطات
        found, misses, wrong, total, correct_times = check_spots_vs_regions(spots_pressed, regions, times)

        # تحليل الأداء
        analysis = analyze_spot_diff(found, total, wrong, misses, correct_times, age)

        # تحويل البيانات لكي تكون صالحة للـ JSON
        regions_clean = convert_regions_to_list(regions)

        # حذف الملفات المؤقتة بعد الاستخدام (اختياري)
        import os
        os.remove(img1_path)
        os.remove(img2_path)

        return jsonify({
            'regions': regions_clean,
            'found': int(found),
            'misses': int(misses),
            'wrong': int(wrong),
            'total_differences': int(total),
            'analysis': analysis
        }), 200

    except Exception as e:
        return jsonify({'error': str(e)}), 500



#//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

activity_pool = {
    "kg1": [
        {"items": ["🍎", "🍌", "🍊", "🐶"], "answer": "🐶", "category": "الفواكه"},
        {"items": ["✏️", "📏", "📕", "🐱"], "answer": "🐱", "category": "أدوات مدرسية"},
    ],
    "kg2": [
        {"items": ["🚗", "🚌", "🚕", "🐴"], "answer": "🐴", "category": "مركبات"},
        {"items": ["🥄", "🍴", "🔪", "🧼"], "answer": "🧼", "category": "أدوات مطبخ"},
    ],
    "kg3": [
        {"items": ["🧹", "🧽", "🪣", "🍰"], "answer": "🍰", "category": "أدوات تنظيف"},
        {"items": ["🌵", "🌲", "🌳", "🏠"], "answer": "🏠", "category": "نباتات"},
    ]
}

SPEED_THRESHOLDS = {
    "kg1": (10, 15),
    "kg2": (9, 13),
    "kg3": (8, 12)
}

# تحديد سرعة الأداء
def get_speed_label(level, time_taken):
    fast, slow = SPEED_THRESHOLDS.get(level, (10, 15))
    if time_taken <= fast:
        return "سريع جدًا"
    elif time_taken <= slow:
        return "جيد"
    else:
        return "بطيء"

# نشاط جديد
@app.route("/classify/generate", methods=["POST"])
def generate_activity():
    data = request.get_json()
    level = data.get("level")
    if not level:
        return jsonify({"error": "Missing level"}), 400

    # تحويل المستوى إلى صيغة صغيرة
    level = str(level).strip().lower()

    if level not in activity_pool:
        return jsonify({"error": f"Invalid level: {level}"}), 400

    activity = random.choice(activity_pool[level])
    max_time = SPEED_THRESHOLDS[level][1]
    activity_id = random.randint(1000, 9999)

    return jsonify({
        "id": activity_id,
        "title": "اختر العنصر الذي لا ينتمي",
        "items": activity["items"],
        "category": activity["category"],
        "correct_answer": activity["answer"],
        "level": level,
        "max_time": max_time
    })

# تقييم التردد
def evaluate_hesitation(click_times):
    if len(click_times) <= 1:
        return "بدون تردد"
    intervals = [click_times[i] - click_times[i-1] for i in range(1, len(click_times))]
    avg_interval = sum(intervals) / len(intervals)
    if avg_interval < 2:
        return "تردد منخفض"
    elif avg_interval < 5:
        return "تردد متوسط"
    else:
        return "تردد مرتفع"

# تقييم الإجابة
@app.route("/api/classify/evaluate", methods=["POST"])
def evaluate_response():
    data = request.get_json()
    selected = data.get("selected")
    correct_answer = data.get("correct_answer")
    time_taken = data.get("time_taken", 0)
    click_times = data.get("click_times", [])
    items = data.get("items", [])
    level = data.get("level", "kg1")

    if selected is None or correct_answer is None or not items:
        return jsonify({"error": "Missing data"}), 400

    level = str(level).strip().lower()
    is_correct = (selected == correct_answer)
    speed_label = get_speed_label(level, time_taken)
    hesitation = evaluate_hesitation(click_times)

    if is_correct and speed_label == "سريع جدًا":
        note = "أداء ممتاز – فهم منطقي سريع"
    elif is_correct:
        note = "إجابة صحيحة لكن يفضل تحسين السرعة"
    elif not is_correct and selected in items:
        note = "خطأ مفاهيمي – بحاجة تعزيز المفهوم"
    else:
        note = "خطأ غير واضح – يُنصح إعادة النشاط بتوجيه"

    return jsonify({
        "result": "صحيح" if is_correct else "خطأ",
        "time_taken": time_taken,
        "performance_speed": speed_label,
        "hesitation": hesitation,
        "note": note
    })






# -------------------------------
# بيانات أسئلة Raven
# -------------------------------
QUESTIONS = [
    {"id": 1, "pattern_image": "A_1.jpeg", "options": {
        1: "A_2.jpeg", 2: "A_3.jpeg", 3: "A_4.jpeg", 4: "A_5.jpeg", 5: "A_6.jpeg", 6: "A_7.jpeg"},
     "correct_option": 2},
    {"id": 2, "pattern_image": "B_1.jpg", "options": {
        1: "B_2.jpg", 2: "B_3.jpg", 3: "B_4.jpg", 4: "B_5.jpg", 5: "B_6.jpg", 6: "B_7.jpg"},
     "correct_option": 3},
    {"id": 3, "pattern_image": "C_1.jpg", "options": {
        1: "C_2.jpg", 2: "C_3.jpg", 3: "C_4.jpg", 4: "C_5.jpg", 5: "C_6.jpg", 6: "C_7.jpg"},
     "correct_option": 1},
    {"id": 4, "pattern_image": "D_1.png", "options": {
        1: "D_2.png", 2: "D_3.png", 3: "D_4.png", 4: "D_5.png", 5: "D_6.png", 6: "D_7.png"},
     "correct_option": 4},
    {"id": 5, "pattern_image": "E_1.jpg", "options": {
        1: "E_2.jpg", 2: "E_3.jpg", 3: "E_4.jpg", 4: "E_5.jpg", 5: "E_6.jpg", 6: "E_7.jpg"},
     "correct_option": 1},
    {"id": 6, "pattern_image": "F_1.jpg", "options": {
        1: "F_2.jpg", 2: "F_3.jpg", 3: "F_4.jpg", 4: "F_5.jpg", 5: "F_6.jpg", 6: "F_7.jpg"},
     "correct_option": 3},
    {"id": 7, "pattern_image": "G_1.jpg", "options": {
        1: "G_2.jpg", 2: "G_3.jpg", 3: "G_4.jpg", 4: "G_5.jpg", 5: "G_6.jpg", 6: "G_7.jpg"},
     "correct_option": 2},
    {"id": 8, "pattern_image": "H_1.jpg", "options": {
        1: "H_2.jpg", 2: "H_3.jpg", 3: "H_4.jpg", 4: "H_5.jpg", 5: "H_6.jpg", 6: "H_7.jpg"},
     "correct_option": 4},
    {"id": 9, "pattern_image": "L_1.jpg", "options": {
        1: "L_2.jpg", 2: "L_3.jpg", 3: "L_4.jpg", 4: "L_5.jpg", 5: "L_6.jpg", 6: "L_7.jpg"},
     "correct_option": 1},
]

# -------------------------------
# Routes
# -------------------------------

@app.route("/")
def home():
    return jsonify({"message": "Raven Progressive Matrices API is running!"})


# استرجاع جميع الأسئلة (بدون correct_option)
@app.route("/questions", methods=["GET"])
def get_questions():
    questions_no_answer = [
        {k: v for k, v in q.items() if k != "correct_option"} for q in QUESTIONS
    ]
    return jsonify({"status": "success", "data": questions_no_answer})

                
# التحقق من الإجابة
@app.route("/check_answer", methods=["POST"])
def check_answer():
    data = request.get_json()
    question_id = data.get("question_id")
    selected_option = data.get("selected_option")

    question = next((q for q in QUESTIONS if q["id"] == question_id), None)
    if not question:
        return jsonify({"status": "error", "message": "Question not found"}), 404

    is_correct = question["correct_option"] == selected_option
    return jsonify({
        "status": "success",
        "data": {
            "question_id": question_id,
            "selected_option": selected_option,
            "is_correct": is_correct
        }
    })



#/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////




# لتخزين المحركات النشطة لكل session_id
engines = {}

def print_arabic(text):
    reshaped_text = arabic_reshaper.reshape(text)
    bidi_text = get_display(reshaped_text)
    print(bidi_text)

def get_arabic(text):
    reshaped_text = arabic_reshaper.reshape(text)
    return get_display(reshaped_text)

CF_TABLE = {
    "بالتأكيد لا": -1.0,
    "شبه مؤكد لا": -0.8,
    "على الأغلب لا": -0.6,
    "ربما لا": -0.4,
    "غير معروف": 0.0,
    "ربما": 0.4,
    "على الأغلب": 0.6,
    "شبه مؤكد نعم": 0.8,
    "بالتأكيد نعم": 1.0,
}
CF_TABLE_level = {
    "ربما": 0.4,
    "على الأغلب": 0.6,
    "شبه مؤكد نعم": 0.8,
    "بالتأكيد نعم": 1.0,
}
CF_TABLE_2 = {
    "بالتأكيد لا": -1.0,
    "بالتأكيد نعم": 1.0,
}

class Answer(Fact):
    ident = Field(str)
    text = Field(str)
    cf = Field(float, default=0.0)

class question(Fact):
    ident = Field(str)
    text = Field(str)
    Type = Field(str)
    valid = Field(list)

class SymptomResult(Fact):
    name = Field(str)
    it=Field(bool ,default=False)
    cf = Field(float)

class DiagnosisResult(Fact):
    diagnosis = Field(str)
    final=Field(str)
    it=Field(bool ,default=False)
    cf = Field(float)

class NeuroDevDiagnosis(KnowledgeEngine):

    @DefFacts()
    def _initial_action(self):
        yield question(
            ident='si_q1',
            text='هل يعاني الطفل من صعوبة في بدء التفاعل مع الآخرين (مثل التحية أو المبادرة بالحديث)؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='si_q2',
            text='هل تجد أن الطفل لا يستجيب عند مناداته أو لا ينظر إلى المتحدث؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='si_q3',
            text='هل يظهر الطفل صعوبة في فهم الإشارات الاجتماعية مثل تعبيرات الوجه أو نبرة الصوت؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='em_q1',
            text='هل يُظهر الطفل صعوبة في مشاركة الآخرين مشاعره أو فهم مشاعرهم(لايتعاطف عند رؤية شخص حزين)؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='em_q2',
            text='هل يجد الطفل صعوبة في التعبير عن عاطفته تجاه المقربين منه؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='em_q3',
            text='هل يواجه الطفل تحديات في الاستجابة لمشاعر الآخرين؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='asd_Aq1',
            text='هل يعاني الطفل من صعوبة في التفاعل بالمثل؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='asd_Aq2',
            text='هل لديه عجز في التواصل غير اللفظي؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='asd_Aq3',
            text='هل يعاني الطفل من صعوبة في بناء العلاقات وفهمها؟ ',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='asd_Bq1',
            text='هل لديه سلوكيات متكررة مثل رفرفة اليدين أو صف الألعاب؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='asd_Bq2',
            text='هل هو روتيني ويرفض التغير؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='asd_Bq3',
            text='هل لديه اهتمامات ضيقة مثل التعلق الشديد بالأشياء الغير عادية؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='asd_Bq4',
            text='هل لديه حساسية زائدة أو ناقصة للأشياء (مثل عدم الاكتراث للألم أو انبهار بصري للضوء)؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='asd_Cq1',
            text='هل ظهرت الأعراض في سن مبكرة؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='asd_Dq1',
            text='هل تؤثر الأعراض على الأداء اليومي؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        
        yield question(
            ident='asd_level_1',
            text='هل يحتاج الطفل لمساعدة مستمرة في تنظيم يومه وأنشطته بسبب صعوبة التحكم في السلوك أو التركيز؟',
            Type='multi',
            valid=list(CF_TABLE_level.keys())
        )
        yield question(
            ident='asd_level_2',
            text='هل يقلل الدعم المتاح (مثل وجود مساعد أو خطة دعم) من شدة أعراض الطفل بشكل ملحوظ؟',
            Type='multi',
            valid=list(CF_TABLE_level.keys())
        )
        yield question( 
            ident='asd_level_3',
            text='هل يستخدم الطفل طرق غير مألوفة أو غريبة للتعبير عن احتياجاته؟',
            Type='multi',
            valid=list(CF_TABLE_level.keys())
        )
        yield question(
            ident='asd_level_4',
            text='هل يجد الطفل صعوبة في تقبّل الأنشطة الجديدة أو الانتقال من نشاط إلى آخر؟',
            Type='multi',
            valid=list(CF_TABLE_level.keys())
        )
        yield question( 
            ident='asd_level_5',
            text='هل يحتاج الطفل لتوجيه دائم في المواقف الاجتماعية ليتمكن من التصرف بشكل مناسب؟',
            Type='multi',
            valid=list(CF_TABLE_level.keys())
        )

        yield question(
            ident='asd_ld',
            text='هل لديه مشاكل في اللغة أو النطق أو الاستخدام الاجتماعي للغة؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='asd_id_gdd',
            text='هل يعاني الطفل من قصور معرفي شامل؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
       
        yield question(
            ident='ld_sli',
            text='هل يعاني ضعف مفردات في الجمل او التعبير ؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='ld_voice',
            text='هل لديه صعوبة نطق الاصوات او يكون كلامه غير واضح ؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='ld_stutter',
            text='هل يتحدث جمل كاملة لكنه يستغرق وقت لتنتهي ؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='ld_spcd',
            text='هل يستخدم اللغة بطريقة غير مناسبة اجتماعيا(عدم تناوب او عدم فهم النكت) ؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='id_gdd1',
            text='هل يعاني من تاخر شامل في المهارات النمائية(فهم لغة حركة) ؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='id_gdd2',
            text='هل هو تحت عمر ال5 سنوات ؟',
            Type='multi',
            valid=list(CF_TABLE_2.keys())
        )
        yield question(
            ident='id_gdd3',
            text='هل لديه اخفاق كبير بالتعليم والتفكير ؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='id_gdd4',
            text='هل لديه سذاجة اجتماعية ؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='id_gdd5',
            text='هل لديه صعوبة بمعرفة الاشياء البديهية (معرفة الوقت او استخدام المال) ؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='sli_1',
            text='هل لديه صعوبة في تركيب الجمل ؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='sli_2',
            text='هل لديه ضعف  في التعبير عن المواقف التي تحدث معه ؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='sli_3',
            text='هل ظهرت الأعراض في سن مبكرة؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='sli_4',
            text='هل تؤثر الأعراض على الأداء اليومي؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='sli_5',
            text='(هل تلاحظ أن صعوبات اللغة عند طفلك ترافقها صعوبات في الفهم أو التعلم أو التصرف؟)',
            Type='multi',
            valid=list(CF_TABLE_2.keys())
        )
        yield question(
            ident='voice_1',
            text='هل ظهرت الأعراض في سن مبكرة؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        
        yield question(
            ident='voice_2',
            text='هل تم استبعاد سبب عضوي او عصبي  (إذا لم يتم فحص الطفل من قبل طبيب، اختر "لا")',
            Type='multi',
            valid=list(CF_TABLE_2.keys())
        )
        yield question(
            ident='stutter_1',
            text='هل يقوم بتكرار مقاطع من الجمل اثناء الحديث',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='stutter_2',
            text='هل يتوقف كثيرا اثناء الكلام قبل اكتمال جملة واحدة',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='stutter_3',
            text='هل يتوتر اثناء الحديث ',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='stutter_4',
            text='هل استمرت الاعراض 6 اشهر او اكثر ؟ ',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='spcd_1',
            text='هل لا يغير اسلوب حديثه حسب السياق ؟ ',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='spcd_2',
            text='هل يشعر بالانزعاج بسبب عدم القدرة على التفريق بين الجد و المزاح ؟ ',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='spcd_3',
            text=' هل يقوم بمقاطعة حديث الاخرين ',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        yield question(
            ident='spcd_4',
            text='هل تظهر الصعوبات فقط في التواصل الاجتماعي دون وجود مشكلات أخرى في السلوك أو النمو( حركة - تعلم)؟',
            Type='multi',
            valid=list(CF_TABLE.keys())
        )
        
        


        yield Fact(ask='si_q1')

    # def ask_user(self, question_text, Type, valid=None):
    #     print()
    #     print_arabic(question_text)
    #     print_arabic("الخيارات:")
    #     for i, option in enumerate(valid):
    #         print(f"{i+1}. {get_arabic(option)}")
    #     while True:
    #         choice = input("اختر رقم الخيار >>> ").strip()
    #         if choice.isdigit():
    #             idx = int(choice) - 1
    #             if 0 <= idx < len(valid):
    #                 selected = valid[idx]
    #                 cf = CF_TABLE.get(selected, 0.0)
    #                 return selected, cf
    #         print_arabic(" اختيار غير صالح، حاول مجددًا")

    # @Rule(question(ident=MATCH.id, text=MATCH.text, Type=MATCH.Type, valid=MATCH.valid),
    #       NOT(Answer(ident=MATCH.id)),
    #       AS.ask_fact << Fact(ask=MATCH.id))
    # def ask_question_by_id(self, ask_fact, id, text, Type, valid):
    #     self.retract(ask_fact)
    #     ans_text, ans_cf = self.ask_user(text, Type, valid)
    #     self.declare(Answer(ident=id, text=ans_text, cf=ans_cf))
    # # new
    def get_current_question(self):
        for fact in self.facts.values():
            if isinstance(fact, Fact) and 'ask' in fact:
                ask_id = fact['ask']
                for f in self.facts.values():
                    if isinstance(f, question) and f['ident'] == ask_id:
                        return {
                            "ident": f['ident'],
                            "text": f['text'],
                            "Type": f['Type'],
                            "valid": f['valid']
                        }
        return None

    def combine_cf(self, cf1, cf2):
        if cf1 >= 0 and cf2 >= 0:
            return cf1 + cf2 * (1 - cf1)
        elif cf1 < 0 and cf2 < 0:
            return cf1 + cf2 * (1 + cf1)
        else:
            return (cf1 + cf2) / (1 - min(abs(cf1), abs(cf2)))



    @Rule(Answer(ident='si_q1'),
          NOT(Fact(ask='si_q2')))
    def ask_si_q2(self):
        self.declare(Fact(ask='si_q2'))

    @Rule(Answer(ident='si_q2'),
          NOT(Fact(ask='si_q3')))
    def ask_si_q3(self):
        self.declare(Fact(ask='si_q3'))

    @Rule(Answer(ident='si_q1', cf=MATCH.cf1),
          Answer(ident='si_q2', cf=MATCH.cf2),
          Answer(ident='si_q3', cf=MATCH.cf3),
          )
    def check_social_disorder(self, cf1, cf2, cf3):
        min_cf = min(cf1, cf2, cf3)
        if min_cf >= 0.4:
            self.declare(SymptomResult(name="social_disorder",it=True, cf=min_cf))
            self.declare(Fact(ask='em_q1'))
        else:
            self.declare(SymptomResult(name="social_disorder",it=False, cf=min_cf))
            self.declare(Fact(route="communication"))
        


    @Rule(Answer(ident='em_q1'),
          NOT(Fact(ask='em_q2')))
    def ask_em_q2(self):
        self.declare(Fact(ask='em_q2'))

    @Rule(Answer(ident='em_q2'),
          NOT(Fact(ask='em_q3')))
    def ask_em_q3(self):
        self.declare(Fact(ask='em_q3'))

    @Rule(Answer(ident='em_q1', cf=MATCH.cf1),
          Answer(ident='em_q2', cf=MATCH.cf2),
          Answer(ident='em_q3', cf=MATCH.cf3),
          )
    def check_emotional_participation(self, cf1, cf2, cf3):
            min_cf = min(cf1, cf2, cf3)
            if min_cf >= 0.4:
                self.declare(SymptomResult(name="emotional_participation",it=True, cf=min_cf))
            else:
                self.declare(SymptomResult(name="emotional_participation",it=False, cf=min_cf))
                self.declare(Fact(route="communication"))

    @Rule(SymptomResult(name="social_disorder",it=True, cf=MATCH.cf1),
          SymptomResult(name="emotional_participation",it=True, cf=MATCH.cf2))
    def check_asd(self, cf1, cf2):
            final_cf = min(cf1, cf2)
            if final_cf >= 0.4:
                
                self.declare(Fact(route="ASD"))   
            else:
                self.declare(Fact(route="communication"))
               
            

###############مخطط التوحد بداية#################
    @Rule(AS.g<<Fact(route="ASD"),   
      NOT(Fact(ASD='done')),
      NOT(Fact(ask='asd_Aq1')))
    def begin_asd_flow(self,g):
        self.retract(g)
        print_arabic("(ASD)الانتقال إلى نظام تشخيص اضطراب التوحد  ...")
        self.declare(Fact(ask='asd_Aq1'))
        self.declare(Fact(ASD='done'))

    @Rule(Answer(ident='asd_Aq1', cf=MATCH.cf),
          NOT(Fact(ask='asd_Aq2')))
    def route_from_asd_Aq1(self, cf,):
        cff=cf*0.8
        if cff >= 0.4:
            self.declare(DiagnosisResult(diagnosis="ASD",final="no", it=True, cf=cff))
            self.declare(Fact(ask='asd_Aq2'))
        else:
            self.declare(Fact(ask='asd_ld'))
            
    @Rule(Answer(ident='asd_Aq2', cf=MATCH.cf),
         AS.f<<DiagnosisResult(diagnosis="ASD",final="no",it=True, cf=MATCH.old_cf),
         NOT(Fact(ask='asd_Aq3')),
         NOT(Fact(ask='asd_ld')))

    def route_from_asd_Aq2(self, cf,old_cf,f):
        cff=cf*0.8
        new_cf = self.combine_cf(old_cf, cff)
        self.modify(f, cf=new_cf)
        if cff >= 0.4:
            self.declare(Fact(ask='asd_Aq3'))
        else:
            self.declare(Fact(ask='asd_ld'))

    @Rule(Answer(ident='asd_Aq3', cf=MATCH.cf),
          AS.f<<DiagnosisResult(diagnosis="ASD",final="no",it=True, cf=MATCH.old_cf),
          NOT(Fact(ask='asd_Bq1')),
          NOT(Fact(ask='asd_ld')))
    
    def route_from_asd_Aq3(self, cf,old_cf,f):
        #يعني لازم جوابو من7 حتى ينتقبل
        cff=cf*0.9
        new_cf = self.combine_cf(old_cf, cff)
        self.modify(f, cf=new_cf)
        if cff >= 0.4:
            self.declare(Fact(ask='asd_Bq1'))
        else:
            self.declare(Fact(ask='asd_ld'))

    @Rule(Answer(ident='asd_Bq1'),
          NOT(Fact(ask='asd_Bq2')))
    def ask_asd_Bq2(self):
        self.declare(Fact(ask='asd_Bq2'))

    @Rule(Answer(ident='asd_Bq2'),
          NOT(Fact(ask='asd_Bq3')))
    def ask_asd_Bq3(self):
        self.declare(Fact(ask='asd_Bq3'))

    @Rule(Answer(ident='asd_Bq3'),
          NOT(Fact(ask='asd_Bq4')))
    def ask_asd_Bq4(self):
        self.declare(Fact(ask='asd_Bq4'))

    @Rule(Answer(ident='asd_Bq1', cf=MATCH.cf1),
          Answer(ident='asd_Bq2', cf=MATCH.cf2),
          Answer(ident='asd_Bq3', cf=MATCH.cf3),
          Answer(ident='asd_Bq4', cf=MATCH.cf4),
          AS.f<<DiagnosisResult(diagnosis="ASD",final="no",it=True, cf=MATCH.old_cf),
          NOT(Fact(ask='asd_Cq1')),
          NOT(Fact(ask='asd_ld')))
    def evaluate_asd_B_block(self, cf1, cf2, cf3, cf4,f,old_cf):
        cff1=cf1*0.9
        cff2=cf2*0.8
        cff3=cf3*0.8
        cff4=cf4*0.6
        cff=min(cff1, cff2, cff3, cff4)
        new_cf = self.combine_cf(old_cf, cff)
        self.modify(f, cf=new_cf)

        count = 0
        for cf in [cff1, cff2, cff3, cff4]:
            if cf >= 0.4:
                count += 1
                
        if count >= 2:
            self.declare(Fact(ask='asd_Cq1'))
        else:
            self.declare(Fact(ask='asd_ld'))
            



    @Rule(Answer(ident='asd_Cq1', cf=MATCH.cf),
          AS.f<<DiagnosisResult(diagnosis="ASD",final="no",it=True, cf=MATCH.old_cf),
          NOT(Fact(ask='asd_Dq1')),
          NOT(Fact(route="ID/GDD"))
          )
    
    def route_from_asd_Cq1(self, cf,f,old_cf):
        #لازم من 7
        cff=cf*0.9
        new_cf = self.combine_cf(old_cf, cff)
        self.modify(f, cf=new_cf)
        if cff >= 0.4:
            
            self.declare(Fact(ask='asd_Dq1'))
        else:
            self.declare(Fact(route="ID/GDD"))

   
    @Rule(Answer(ident='asd_Dq1', cf=MATCH.cf), 
      AS.f << DiagnosisResult(diagnosis="ASD", final="no", it=True, cf=MATCH.old_cf),
      NOT(Fact(route="ID/GDD")))
    def route_from_asd_Dq1(self, f, cf, old_cf):
        cff = cf * 0.9
        if cff >= 0.4:
            new_cf = self.combine_cf(old_cf, cff)
            self.retract(f)
            self.declare(DiagnosisResult(diagnosis="ASD", final="yes", it=True, cf=new_cf))
        else:
            self.retract(f)
            self.declare(DiagnosisResult(diagnosis="ASD", final="no", it=True, cf=old_cf))
            self.declare(Fact(route="ID/GDD"))

#نسبة توقع التوحد بدون تحديد مستوى لانه اقل من 70%
    @Rule(AS.o <<DiagnosisResult(diagnosis="ASD", final="no", it=True, cf=MATCH.old_cf),
       OR(Fact(route="ID/GDD"), Fact(ask="asd_ld")),
       TEST(lambda old_cf: 0< old_cf < 0.7))  # هذا الشرط يضمن إنو خرج من المخطط
    def when_asd_partial_exit(self, old_cf,o):
        print_arabic("\nتم استبعاد تشخيص التوحد")
        print_arabic("نسبةالشك قليلة")
        print(round(old_cf * 100, 2), "%")
        self.retract(o)

        #نسبة توقع التوحد مع تحديد مستوى لانه اكبر من 70%
    @Rule(AS.o <<DiagnosisResult(diagnosis="ASD", final="no", it=True, cf=MATCH.old_cf),
       OR(Fact(route="ID/GDD"), Fact(ask="asd_ld")),
       NOT(Fact(state="final")),
       TEST(lambda old_cf: old_cf >= 0.7))
    def when_asd_partial_exit2(self, old_cf,o):
        print_arabic("\nاحتمال التوحد مرتفع")
        print(round(old_cf * 100, 2), "%")
        print_arabic("ننتقل الآن لتحديد مستوى الشدة لتأكيد التشخيص")
        self.declare(Fact(state="final"))
        self.declare(Fact(route="level"))
        self.retract(o)

    
       #نسبة توقع التوحد  100%
    @Rule(DiagnosisResult(diagnosis="ASD", final="yes", it=True, cf=MATCH.old_cf),
          NOT(Fact(state="final")))
    def when_asd_confirmed(self, old_cf):
        self.declare(Fact(state="final"))
        print_arabic("\nاحتمال التوحد مؤكد ")
        print(round(old_cf * 100, 2), "%")
        print_arabic(" ننتقل الآن لتحديد مستوى الشدة ")
        # cff=old_cf*100
        # print(cff)
        self.declare(Fact(route="level"))
        
       
        # self.halt()



###################تحديد مستوى التوحد#######################
    
    @Rule(Fact(state="final"),
          AS.g <<Fact(route="level"),
          NOT(Fact(ask='asd_level_1')))
    def ask_level_1_question(self,g):
        self.retract(g)
        print_arabic("استمر في الاجابة...كي نحدد مستوى الاضطراب")
        self.declare(Fact(ask='asd_level_1'))
        
    @Rule(Answer(ident='asd_level_1'),
            NOT(Fact(ask='asd_level_2')))
    def ask_level_2_question(self):
        self.declare(Fact(ask='asd_level_2'))

    @Rule(Answer(ident='asd_level_2'),
            NOT(Fact(ask='asd_level_3')))
    def ask_level_3_question(self):
        self.declare(Fact(ask='asd_level_3'))

    @Rule(Answer(ident='asd_level_3'),
            NOT(Fact(ask='asd_level_4')))
    def ask_level_4_question(self):
        self.declare(Fact(ask='asd_level_4'))

    @Rule(Answer(ident='asd_level_4'),
            NOT(Fact(ask='asd_level_5')))
    def ask_level_5_question(self):
        self.declare(Fact(ask='asd_level_5'))
    #تحديد المستوى
    @Rule(Answer(ident='asd_level_1', cf=MATCH.cf1),
          Answer(ident='asd_level_2', cf=MATCH.cf2),
          Answer(ident='asd_level_3', cf=MATCH.cf3),
          Answer(ident='asd_level_4', cf=MATCH.cf4),
          Answer(ident='asd_level_5', cf=MATCH.cf5),
          NOT(Fact(all="end")))
    def level(self,cf1,cf2,cf3,cf4,cf5):
        self.declare(Fact(all="end"))
        cff=(cf1+cf2+cf3+cf4+cf5)/5
        if cff < 0.5:
            self.declare(Fact(level=1))
        elif cff < 0.75:
            self.declare(Fact(level=2))
        else:
            self.declare(Fact(level=3))
         

    @Rule(Fact(level=1))
    def test_level_1(self):
        print_arabic(" تم تأكيد أن الشدة من المستوى 1")
        print_arabic(" يحتاج إلى دعم. يعاني من صعوبات ملحوظة في التواصل دون دعم، وصعوبة في بدء التفاعل الاجتماعي")
        print_arabic(" العلاج: خطة دعم فردية، تدريب على المهارات الاجتماعية، ودمج في الأنشطة الجماعية مع إشراف خفيف")
        self.halt()

    @Rule(Fact(level=2))
    def test_level_2(self):
        print_arabic(" تم تأكيد أن الشدة من المستوى 2")
        print_arabic(" يحتاج إلى دعم كبير. يواجه صعوبة واضحة في التواصل حتى مع وجود دعم")
        print_arabic(" العلاج: جلسات تدخل سلوكي مكثفة، علاج نطق، وبيئة تعليمية مهيأة تراعي احتياجاته")
        self.halt()
    @Rule(Fact(level=3))
    def test_level_3(self):
        print_arabic(" تم تأكيد أن الشدة من المستوى 3")
        print_arabic(" يحتاج إلى دعم كبير جدًا. يعاني من عجز شديد في التواصل، وسلوكيات متكررة تؤثر في جميع السياقات")
        print_arabic(" العلاج: دعم مستمر على مدار اليوم، خطة تدخل شاملة، وإشراف متعدد التخصصات (نطق، سلوك، تربية خاصة)")
        self.halt()

    @Rule(Answer(ident='asd_ld', cf=MATCH.cf))
    def route_from_asd_lang_deficit(self, cf):
        if cf >= 0.4:
            self.declare(Fact(route="SLI"))
        else:
            self.declare(Fact(ask='asd_id_gdd'))

    @Rule(Answer(ident='asd_id_gdd', cf=MATCH.cf))
    def route_from_asd_id_check(self, cf):
        if cf >= 0.4:
            self.declare(Fact(route="ID/GDD"))
        else:
            #  print_arabic("طفلك لا يعاني من شيئ")
             print_arabic(" لم يتم التوصّل إلى تشخيص واضح بناءً على المعلومات المتوفرة حاليًا")
             print_arabic(" قد تكون الأعراض غير كافية أو غير محددة. ننصح بإعادة التقييم بعد فترة من الزمن أو استشارة مختص")
             self.halt()
              
###############نهاية مخطط التوحد################
         
###############بداية مخطط التواصل################
    @Rule(AS.g<<Fact(route="communication"),
          NOT(Fact(communication='done')),
      NOT(Fact(ask='ld_sli')))
    def begin_communication_flow(self,g):
        self.retract(g)
        print_arabic(" الانتقال إلى نظام تشخيص اضطرابات التواصل...")
        self.declare(Fact(ask='ld_sli'))
        self.declare(Fact(communication='done'))

    @Rule(Answer(ident='ld_sli', cf=MATCH.cf))
    def route_from_communication_sli(self, cf):
        if cf >= 0.4:
            
            self.declare(Fact(route="SLI"))            
        else:
            self.declare(Fact(ask='ld_voice'))

    @Rule(Answer(ident='ld_voice', cf=MATCH.cf))
    def route_from_communication_voice(self, cf):
        if cf >= 0.4:
            self.declare(Fact(route="VOICE"))
            print("voice")
        else:
            self.declare(Fact(ask='ld_stutter'))    

    @Rule(Answer(ident='ld_stutter', cf=MATCH.cf))
    def route_from_communication_stutter(self, cf):
        if cf >= 0.4:
            self.declare(Fact(route="STUTTERING"))
            print("stutter")
        else:
            self.declare(Fact(ask='ld_spcd')) 

    @Rule(Answer(ident='ld_spcd', cf=MATCH.cf))
    def route_from_communication_spcd(self, cf):
        if cf >= 0.4:
            self.declare(Fact(route="SPCD"))
            print("spp")
        else:
             self.declare(Fact(route="ID/GDD"))
############### نهاية مخطط التواصل################
   
    def print_no_diagnosis(self):
        print_arabic(" لم يتم التوصّل إلى تشخيص واضح بناءً على المعلومات المتوفرة حاليًا")
        print_arabic(" قد تكون الأعراض غير كافية أو غير محددة. ننصح بإعادة التقييم بعد فترة من الزمن أو استشارة مختص")

    @Rule(Fact(ASD='done'),
    Fact(route="ASD"))
    def print_1(self):
            self.print_no_diagnosis()
            self.halt()

    @Rule(Fact(ID_GDD='done'),
    Fact(route="ID/GDD"))
    def print_2(self):
            self.print_no_diagnosis()
            self.halt()

    @Rule(Fact(VOICE='done'),
    Fact(route="VOICE"))
    def print_3(self):
            self.print_no_diagnosis()
            self.halt()

    @Rule(Fact(SLI='done'),
    Fact(route="SLI"))
    def print_4(self):
            self.print_no_diagnosis()
            self.halt()

    @Rule(Fact(STUTTERING='done'),
    Fact(route="STUTTERING"))
    def print_5(self):
            self.print_no_diagnosis()
            self.halt()

    @Rule(Fact(SPCD='done'),
    Fact(route="SPCD"))
    def print_6(self):
            self.print_no_diagnosis()
            self.halt()

############### مخطط ID/GDD بداية################
    @Rule(AS.g<<Fact(route="ID/GDD"),
        NOT(Fact(ID_GDD='done')),
        NOT(Fact(ask='id_gdd1')),
        NOT(DiagnosisResult(diagnosis="ID")))
    def begin_id_gdd_flow(self,g):
        self.retract(g)
        print_arabic("(ID/GDD) الانتقال إلى نظام  ...")
        self.declare(Fact(ID_GDD='done'))
        self.declare(Fact(ask='id_gdd1'))

    @Rule(Answer(ident='id_gdd1', cf=MATCH.cf))
    def route_from_gdd_1(self, cf):
        cff=cf*0.7
        if cff >= 0.4:
            self.declare(Fact(ask='id_gdd2'))
            self.declare(DiagnosisResult(diagnosis="GDD", it=False, cf=cff))
            self.declare(DiagnosisResult(diagnosis="ID", it=False, cf=cff))
        else:
            self.declare(Fact(route="SPCD"))

    @Rule(Answer(ident='id_gdd2', cf=MATCH.cf1),
          AS.g <<(DiagnosisResult(diagnosis="GDD", it=False, cf=MATCH.cf2)),
          )
    def route_from_gdd2(self, cf1,cf2):
        cff=cf1*0.7
        cf_final=self.combine_cf(cff,cf2)
        if cff == 0.7:
            self.declare(DiagnosisResult(diagnosis="GDD", it=True, cf=cf_final))          
        else:
            self.declare(Fact(ask='id_gdd3'))

#اذا طلع معو GDD
    @Rule(AS.f<<DiagnosisResult(diagnosis="GDD", it=True, cf=MATCH.cf))
    def end_GDD(self,cf,f):
        print_arabic("\nالتشخيص النهائي:\n تأخر النمو الشامل (GDD)")
        print_arabic("يتأخر الطفل في أكثر من مجال من مجالات النمو (مثل الحركة، اللغة، أو التفاعل الاجتماعي)، مقارنة بأقرانه. غالبًا ما يُكتشف في سن مبكرة، ويحتاج إلى تقييم ومتابعة مستمرة")
        print_arabic("\n النسبة الظاهرة تشير إلى درجة التأكد من وجود هذا الاضطراب، وليست شدة الحالة") 
        print(round(cf * 100, 2), "%")
        self.retract(f)

#او نكفي بالID

    @Rule(Answer(ident='id_gdd3'),
          NOT(Fact(ask='id_gdd4')))
    def route_from_gdd3(self):
        self.declare(Fact(ask='id_gdd4'))

    @Rule(Answer(ident='id_gdd4'),
          NOT(Fact(ask='id_gdd5')))
    def route_from_gdd4(self):
        self.declare(Fact(ask='id_gdd5'))

    @Rule(Answer(ident='id_gdd3', cf=MATCH.cf1),
          Answer(ident='id_gdd4', cf=MATCH.cf2),
          Answer(ident='id_gdd5', cf=MATCH.cf3),
          AS.f<<(DiagnosisResult(diagnosis="ID", it=False, cf=MATCH.cf)))
    def route_from_gdd_end(self,f,cf, cf1, cf2, cf3):
        valid_cfs = [i for i in [cf1, cf2, cf3] if i >= 0.4]
        if len(valid_cfs) >= 2:
            cff = min(valid_cfs)
            new_cf=self.combine_cf(cf, cff)
            self.modify(f,it =True, cf=new_cf)
                
        else:
            self.declare(Fact(route="SPCD"))

#اذا طلع معو ID
    @Rule(AS.f<<DiagnosisResult(diagnosis="ID", it=True, cf=MATCH.cf))
    def end_ID(self,cf,f):
        # print_arabic(" التشخيص النهائي: اعاقة ذهنية  ")
        print_arabic("\nالتشخيص النهائي:\n الإعاقة الذهنية (ID)")
        print_arabic("يوجد تأخر في القدرات العقلية العامة مثل الفهم، التفكير، ومهارات الحياة اليومية. يحتاج الطفل إلى دعم دائم وبرامج تعليمية خاصة تساعده على التطور حسب قدراته")
        print_arabic("\n النسبة الظاهرة تشير إلى درجة التأكد من وجود هذا الاضطراب، وليست شدة الحالة")
        print(round(cf * 100, 2), "%")
        self.retract(f)

############### مخطط ID/GDDنهاية  ################

   #######بداية SLI#####
    @Rule(AS.g<<Fact(route="SLI"),
          NOT(Fact(ask='sli_1')),
          NOT(DiagnosisResult(diagnosis="SLI")),
          NOT(Fact(SLI='done')))
    def sli_1(self,g):
        self.retract(g)
        print_arabic("الانتقال إلى نظام  (SLI) ...")
        self.declare(Fact(ask='sli_1'))
        self.declare(Fact(SLI='done'))
        
    @Rule(Answer(ident='sli_1'),
          NOT(Fact(ask='sli_2')))
    def sli_2(self):
        self.declare(Fact(ask='sli_2'))

    @Rule(
          Answer(ident='sli_1', cf=MATCH.cf1),
          Answer(ident='sli_2', cf=MATCH.cf2),
          )
    def sli(self,cf1, cf2):
        valid_cfs = [i for i in [cf1, cf2] if i >= 0.4]
        if len(valid_cfs) >= 1:
            cff = min(valid_cfs)
            self.declare(Fact(ask='sli_3'))
            self.declare(DiagnosisResult(diagnosis="SLI", it=False, cf=cff))
        else:
            self.declare(Fact(route="SPCD"))


    @Rule(Answer(ident='sli_3',cf=MATCH.cf),
          NOT(Fact(ask='sli_4')),
          AS.f<<DiagnosisResult(diagnosis="SLI", it=False, cf=MATCH.cf2))
    def sli_3(self,cf,cf2,f):
        cff=cf*0.7
        if cff >= 0.4:
            new_cf=self.combine_cf(cf2, cff)
            self.modify(f, cf=new_cf)
            self.declare(Fact(ask='sli_4'))
        else:
            self.declare(Fact(route="ID/GDD"))

    @Rule(Answer(ident='sli_4', cf=MATCH.cf),
           AS.f<<DiagnosisResult(diagnosis="SLI", it=False, cf=MATCH.cf2))
    def sli_4(self,f,cf,cf2):
        cff=cf*0.8
        if cff >= 0.4:
            new_cf=self.combine_cf(cf2, cff)
            self.modify(f, cf=new_cf)
            self.declare(Fact(ask='sli_5'))
            
        else:
            self.declare(Fact(route="ID/GDD"))

    @Rule(Answer(ident='sli_5',cf=MATCH.cf),
          AS.f<<DiagnosisResult(diagnosis="SLI", it=False, cf=MATCH.cf2))
    def sli_end(self,cf,cf2,f):
        cff=cf*0.8
        if cff == 0.8:
            new_cf=self.combine_cf(cf2, cff)
            self.modify(f, it=True,cf=new_cf)
        else:
            self.declare(Fact(route="ID/GDD"))
    
    @Rule(AS.f<<DiagnosisResult(diagnosis="SLI", it=True, cf=MATCH.cf))
    def end_SLI(self,cf,f):
        print_arabic("\nالتشخيص النهائي:\n اضطراب اللغة النمائي النوعي (SLI)")
        print_arabic("يعاني الطفل من صعوبات في اكتساب اللغة وفهمها أو التعبير بها، رغم أن تطوره السمعي والمعرفي طبيعي. لا توجد أسباب طبية واضحة لهذه الصعوبات، لكنه يحتاج إلى دعم لغوي خاص لتحسين تواصله")
        print_arabic("\n النسبة الظاهرة تشير إلى درجة التأكد من وجود هذا الاضطراب، وليست شدة الحالة")
        print(round(cf * 100, 2), "%")
        self.retract(f)
 #######SLI END########


###اضطراب الكلام بداية /VOICE###
    @Rule(AS.g<<Fact(route="VOICE"),
          NOT(Fact(ask='voice_1')),
          NOT(Fact(VOICE='done')),
          NOT(DiagnosisResult(diagnosis="VOICE")))
    def voice_1(self,g):
        self.declare(Fact(VOICE='done'))
        self.retract(g)
        print_arabic("الانتقال إلى نظام  (SSD) ...")
        self.declare(Fact(ask='voice_1'))

    @Rule(Answer(ident='voice_1', cf=MATCH.cf))
    def voice_2(self, cf):
        cff=cf*0.7
        if cff >= 0.4:
            self.declare(DiagnosisResult(diagnosis="VOICE", it=False, cf=cff))
            self.declare(Fact(ask='voice_2'))
            
        else:
            self.declare(Fact(route="ID/GDD"))

    @Rule(Answer(ident='voice_2',cf=MATCH.cf),
          AS.f<<(DiagnosisResult(diagnosis="VOICE", it=False, cf=MATCH.cf2)))

    def voice_end(self,f,cf,cf2):
        cff=cf*0.8
        if cff ==0.8 :
            new_cf=self.combine_cf(cf2, cff)
            self.modify(f, it=True,cf=new_cf)
           
        else:
            self.declare(Fact(route="ID/GDD"))

    @Rule(AS.f<<DiagnosisResult(diagnosis="VOICE", it=True, cf=MATCH.cf))
    def end_VOICE(self,cf,f):
        print_arabic("\nالتشخيص النهائي:\n اضطراب صوت الكلام (Speech Sound Disorder)")
        print_arabic("يعاني الطفل من صعوبة في نطق بعض الأصوات بشكل صحيح، مما يجعل كلامه أحيانًا غير واضح أو غير مفهوم للآخرين. يتطلب ذلك جلسات نطق لمساعدته على تحسين وضوح الكلام")
        print_arabic("\n النسبة الظاهرة تشير إلى درجة التأكد من وجود هذا الاضطراب، وليست شدة الحالة")
        print(round(cf * 100, 2), "%")
        self.retract(f)
#######VOICE END############


#######بداية مخططSTUTTERING#####
    @Rule(AS.g<<Fact(route="STUTTERING"),
          NOT(Fact(ask='stutter_1')),
          NOT(Fact(STUTTERING='done')),
          NOT(DiagnosisResult(diagnosis="STUTTERING")))
    def stutter_1(self,g):
        self.retract(g)
        print_arabic("🔁 الانتقال إلى نظام  (STUTTERING) ...")
        self.declare(Fact(ask='stutter_1'))
        self.declare(Fact(STUTTERING='done'))
    @Rule(Answer(ident='stutter_1', cf=MATCH.cf))
    def stutter_2(self):
        self.declare(Fact(ask='stutter_2'))    

    @Rule(Answer(ident='stutter_2', cf=MATCH.cf))
    def stutter_3(self):
        self.declare(Fact(ask='stutter_3'))
        
    @Rule(Answer(ident='stutter_1', cf=MATCH.cf1),
          Answer(ident='stutter_2', cf=MATCH.cf2),
          Answer(ident='stutter_3', cf=MATCH.cf3))
    def stutter_4(self, cf1, cf2, cf3):
        cff1=cf1*0.7
        cff2=cf2*0.8
        cff3=cf3*0.8
        valid_cfs = [i for i in [cff1, cff2,cff3] if i >= 0.4]
        if len(valid_cfs) >= 2:
            cff = min(valid_cfs)
            self.declare(DiagnosisResult(diagnosis="STUTTERING", it=False, cf=cff))
            self.declare(Fact(ask='stutter_4'))
        else:
            self.declare(Fact(route="SPCD"))

            
    @Rule(Answer(ident='stutter_4',cf=MATCH.cf),
          AS.f<<(DiagnosisResult(diagnosis="STUTTERING", it=False, cf=MATCH.cf2)))
    def stutter_end(self,f,cf,cf2):
        cff=cf*0.7
        if cff >=0.4:
            new_cf=self.combine_cf(cf2, cff)
            self.modify(f, it=True,cf=new_cf)
            # self.halt()
        else:
            self.declare(Fact(route="SPCD"))

    @Rule(AS.f<<DiagnosisResult(diagnosis="STUTTERING", it=True, cf=MATCH.cf))
    def end_STUTTER(self,cf,f):
        print_arabic("\nالتشخيص النهائي:\n اضطراب الطلاقة (Stuttering - التأتأة)")
        print_arabic("يتكرر لدى الطفل تقطّع أو تكرار في الأصوات أو الكلمات أثناء الكلام، مما قد يؤثر على طلاقته وثقته بالتواصل. يحتاج إلى دعم وتدريب لتحسين الطلاقة")
        print_arabic("\n النسبة الظاهرة تشير إلى درجة التأكد من وجود هذا الاضطراب، وليست شدة الحالة")
        print(round(cf * 100, 2), "%")
        self.retract(f)
 #######نهاية مخططSTUTTERING#####



            #######SPCDبداية مخطط#####
    @Rule(AS.g<<Fact(route="SPCD"),
          NOT(Fact(ask='spcd_1')),
          NOT(Fact(SPCD='done')),
          NOT(DiagnosisResult(diagnosis="SPCD")))
    def spcd_1(self,g):
        self.retract(g)
        print_arabic(" الانتقال إلى نظام (SPCD) ...")
        self.declare(Fact(ask='spcd_1'))
        self.declare(Fact(SPCD='done'))
    @Rule(Answer(ident='spcd_1', cf=MATCH.cf))
    def spcd_2(self):
        self.declare(Fact(ask='spcd_2'))    

    @Rule(Answer(ident='spcd_2', cf=MATCH.cf))
    def spcd_3(self):
        self.declare(Fact(ask='spcd_3'))
        
    @Rule(Answer(ident='spcd_1', cf=MATCH.cf1),
          Answer(ident='spcd_2', cf=MATCH.cf2),
          Answer(ident='spcd_3', cf=MATCH.cf3))
    def spcd_4(self, cf1, cf2, cf3):
        cff1=cf1*0.8
        cff2=cf2*0.7
        cff3=cf3*0.7
        valid_cfs = [i for i in [cff1, cff2,cff3] if i >= 0.4]
        if len(valid_cfs) >= 1:
            cff = min(valid_cfs)
            self.declare(DiagnosisResult(diagnosis="SPCD", it=False, cf=cff))
            self.declare(Fact(ask='spcd_4'))
        else:
            print("hfhfhfh")
            self.declare(Fact(route="ASD"))

            
    @Rule(Answer(ident='spcd_4',cf=MATCH.cf),
          AS.f<<DiagnosisResult(diagnosis="SPCD", it=False, cf=MATCH.cf2))
    def spcd_end(self,f,cf,cf2):
        cff=cf*0.8
        if cff >=0.4:
            new_cf=self.combine_cf(cf2, cff)
            self.modify(f, it=True,cf=new_cf)
            
        else:
            self.declare(Fact(route="ASD"))


    @Rule(AS.f<<DiagnosisResult(diagnosis="SPCD", it=True, cf=MATCH.cf))
    def end_SPCD(self,cf,f):
        print_arabic(" \nالتشخيص النهائي:\n :اضطراب التواصل الاجتماعي البراغماتي (SPCD)")
        print_arabic("يواجه الطفل صعوبة في استخدام اللغة بطريقة مناسبة في المواقف الاجتماعية، مثل بدء الحديث، أخذ الدور في المحادثة، أو فهم التلميحات \n اللغة بحد ذاتها سليمة، لكن استخدامها غير فعال اجتماعيًا")
        print_arabic(" \n النسبة الظاهرة تشير إلى درجة التأكد من وجود هذا الاضطراب، وليست شدة الحالة")
        print(round(cf * 100, 2), "%")
        self.retract(f)


@app.route('/start', methods=['GET'])
def start():
    engine = NeuroDevDiagnosis()
    engine.reset()
    session_id = str(uuid.uuid4())
    engines[session_id] = engine
    question = engine.get_current_question()
    if question is None:
        return jsonify({"error": "No questions available"}), 400
    return jsonify({
        "session_id": session_id,
        "question": question
    })


@app.route('/answer', methods=['POST'])
def answer():
    data = request.json
    session_id = data.get('session_id')
    engine = engines.get(session_id)
    if engine is None:
        return jsonify({'error': 'Invalid or missing session_id'}), 400

    ident = data.get('ident')
    user_answer_text = data.get('answer')
    
    if ident is None or user_answer_text is None:
        return jsonify({'error': 'Missing ident or answer'}), 400
# ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    try:
        
        CLASSIFY_URL = "https://bb824add7c2f.ngrok-free.app/classify"  
        resp = requests.post(CLASSIFY_URL, json={'text': user_answer_text})

        resp.raise_for_status()
        classification = resp.json().get('label')
    except Exception as e:
        return jsonify({'error': f'Error calling classification service: {str(e)}'}), 500

    cf = CF_TABLE.get(classification, 0.0)

    engine.declare(Answer(ident=ident, text=classification, cf=cf))
    engine.run()

    for fact in engine.facts.values():
        if isinstance(fact, question) and not any(a['ident'] == fact['ident'] for a in engine.facts.values() if isinstance(a, Answer)):
            return jsonify({
                'ident': fact['ident'],
                'text': fact['text'],
                'valid': fact['valid']
            })


    diagnosis = [f for f in engine.facts.values() if isinstance(f, DiagnosisResult)]
    if diagnosis:
        return jsonify({'diagnosis': diagnosis[0]['diagnosis'], 'cf': diagnosis[0]['cf']})

    return jsonify({'message': 'No more questions or results available'})
















if __name__ == "__main__":
    app.run(port=5000, debug=True)
