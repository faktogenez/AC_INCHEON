<?php
declare(strict_types=1);

$dbFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'ac_shop.db';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec('CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name_ru TEXT NOT NULL,
    name_ko TEXT NOT NULL,
    price TEXT NOT NULL,
    desc_ru TEXT,
    desc_ko TEXT,
    image TEXT,
    status TEXT DEFAULT "available",
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)');

$items = [
    [
        'name' => 'LG 휘센 오브제컬렉션 타워 (FQ18PDNBT1)',
        'price' => '3500000',
        'desc_ru' => implode("\n", [
            'Новые модели',
            'Площадь помещения: 18평 (59㎡)',
            'Энергоэффективность: 2등급',
            'Инверторная технология: ✅ (듀얼 인버터)',
        ]),
        'desc_ko' => implode("\n", [
            '신제품',
            '면적: 18평 (59㎡)',
            '에너지 효율: 2등급',
            '인버터: ✅ (듀얼 인버터)',
        ]),
    ],
    [
        'name' => 'LG 휘센 오브제컬렉션 위너 (FQ18HDWHY1)',
        'price' => '1060000',
        'desc_ru' => implode("\n", [
            'Новые модели',
            'Площадь помещения: 18평 (59㎡)',
            'Энергоэффективность: 3등급',
            'Инверторная технология: ✅',
        ]),
        'desc_ko' => implode("\n", [
            '신제품',
            '면적: 18평 (59㎡)',
            '에너지 효율: 3등급',
            '인버터: ✅',
        ]),
    ],
    [
        'name' => '삼성 무풍 클래식 청정 (AF19R7573WSN)',
        'price' => '2100000',
        'desc_ru' => implode("\n", [
            'Новые модели',
            'Площадь помещения: 19평 (63㎡)',
            'Энергоэффективность: 4등급',
            'Инверторная технология: ✅ (디지털 인버터)',
        ]),
        'desc_ko' => implode("\n", [
            '신제품',
            '면적: 19평 (63㎡)',
            '에너지 효율: 4등급',
            '인버터: ✅ (디지털 인버터)',
        ]),
    ],
    [
        'name' => 'LG 휘센 듀얼 빅토리 (FQ18VDDHA1)',
        'price' => '650000',
        'desc_ru' => implode("\n", [
            'Б/У',
            'Площадь помещения: 18평 (59㎡)',
            'Энергоэффективность: 3등급',
            'Инверторная технология: ✅',
        ]),
        'desc_ko' => implode("\n", [
            '중고',
            '면적: 18평 (59㎡)',
            '에너지 효율: 3등급',
            '인버터: ✅',
        ]),
    ],
    [
        'name' => '삼성 무풍 2in1 인버터 (2023년 제조)',
        'price' => '1200000',
        'desc_ru' => implode("\n", [
            'Б/У',
            'Площадь помещения: 17+6평 (56㎡)',
            'Энергоэффективность: 4등급',
            'Инверторная технология: ✅',
            'Год: 2023',
        ]),
        'desc_ko' => implode("\n", [
            '중고',
            '면적: 17+6평 (56㎡)',
            '에너지 효율: 4등급',
            '인버터: ✅',
            '제조: 2023년',
        ]),
    ],
    [
        'name' => 'LG 인버터 냉난방기 30평형 (2022년식)',
        'price' => '1300000',
        'desc_ru' => implode("\n", [
            'Б/У',
            'Площадь помещения: 30평 (99㎡)',
            'Энергоэффективность: 3등급',
            'Инверторная технология: ✅',
            'Год: 2022',
        ]),
        'desc_ko' => implode("\n", [
            '중고',
            '면적: 30평 (99㎡)',
            '에너지 효율: 3등급',
            '인버터: ✅',
            '제조: 2022년',
        ]),
    ],
];

$check = $pdo->prepare('SELECT COUNT(1) FROM products WHERE name_ko = ? AND price = ?');
$insert = $pdo->prepare('INSERT INTO products (name_ru, name_ko, price, desc_ru, desc_ko, image, status) VALUES (?, ?, ?, ?, ?, ?, ?)');

$inserted = 0;
foreach ($items as $it) {
    $check->execute([$it['name'], $it['price']]);
    if ((int)$check->fetchColumn() > 0) {
        continue;
    }
    $ok = $insert->execute([
        $it['name'],
        $it['name'],
        $it['price'],
        $it['desc_ru'],
        $it['desc_ko'],
        null,
        'available',
    ]);
    if ($ok) {
        $inserted++;
    }
}

echo "Inserted: {$inserted}\n";
