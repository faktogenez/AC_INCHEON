<?php
// Модуль: FAQ (вопросы-ответы)
global $PROJECT_CONFIG, $lang;

$faq_items = $PROJECT_CONFIG['faq'][$lang];
?>
<section class="section faq" aria-labelledby="faq-title">
    <div class="section-head">
        <h2 class="section-title" id="faq-title"><?php echo $lang == 'ru' ? 'Частые вопросы' : '자주 묻는 질문'; ?></h2>
    </div>
    <div class="faq-list">
        <?php foreach ($faq_items as $item): ?>
            <details class="faq-item">
                <summary class="faq-question"><?php echo htmlspecialchars($item['q']); ?></summary>
                <div class="faq-answer"><?php echo nl2br(htmlspecialchars($item['a'])); ?></div>
            </details>
        <?php endforeach; ?>
    </div>
</section>
