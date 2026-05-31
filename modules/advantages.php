<?php
// Модуль: Преимущества (цифры, факты)
global $PROJECT_CONFIG, $lang;

$advantages = $PROJECT_CONFIG['advantages'][$lang];
?>
<section class="section advantages" aria-labelledby="advantages-title">
    <div class="section-head">
        <h2 class="section-title" id="advantages-title"><?php echo $lang == 'ru' ? 'Почему выбирают нас' : '선택 이유'; ?></h2>
    </div>
    <div class="advantages-grid">
        <?php foreach ($advantages as $item): ?>
            <div class="advantage-card">
                <div class="advantage-value"><?php echo htmlspecialchars($item['value']); ?></div>
                <div class="advantage-label"><?php echo htmlspecialchars($item['label']); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
