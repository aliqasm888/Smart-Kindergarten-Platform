<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تقرير الطفل</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; direction: rtl; line-height: 1.5; }
        h2 { text-align: center; }
        .section { margin-bottom: 20px; }
        .activity { margin-bottom: 10px; padding: 5px; border-bottom: 1px solid #ccc; }
    </style>
</head>
<body>
<h2>تقرير أداء الطفل: {{ $child_name }}</h2>

<div class="section">
    <h3>ملخص الأداء</h3>
    <p>{{ $summary }}</p>
</div>

<div class="section">
    <h3>التوصيات</h3>
    <ul>
        @foreach($recommendations as $rec)
            <li>{{ $rec }}</li>
        @endforeach
    </ul>
</div>

<div class="section">
    <h3>تفاصيل الأنشطة</h3>
    @foreach($details as $detail)
        <div class="activity">
            <p>{{ $detail }}</p>
        </div>
    @endforeach
</div>
</body>
</html>
