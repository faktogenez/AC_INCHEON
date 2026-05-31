<?php
// Модуль: Преимущества (цифры, факты)
global $PROJECT_CONFIG, $lang;

$advantages = $PROJECT_CONFIG['advantages'][$lang];
?>
<div class="advantages">
    <h2 class="section-title"><?php echo $lang == 'ru' ? 'Почему выбирают нас' : '선택 이유'; ?></h2>
    <div class="advantages-grid">
        <?php foreach ($advantages as $item): ?>
            <div class="advantage-item">
                <div class="advantage-value"><?php echo htmlspecialchars($item['value']); ?></div>
                <div class="advantage-label"><?php echo htmlspecialchars($item['label']); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
