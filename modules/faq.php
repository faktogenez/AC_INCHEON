<?php
// Модуль: FAQ (вопросы-ответы)
global $PROJECT_CONFIG, $lang;

$faq_items = $PROJECT_CONFIG['faq'][$lang];
?>
<div class="faq">
    <h2 class="section-title"><?php echo $lang == 'ru' ? 'Частые вопросы' : '자주 묻는 질문'; ?></h2>
    <div class="faq-list">
        <?php foreach ($faq_items as $index => $item): ?>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <?php echo htmlspecialchars($item['q']); ?>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer"><?php echo nl2br(htmlspecialchars($item['a'])); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleFaq(element) {
    let answer = element.nextElementSibling;
    let toggle = element.querySelector('.faq-toggle');
    if (answer.style.display === 'block') {
        answer.style.display = 'none';
        toggle.textContent = '+';
    } else {
        answer.style.display = 'block';
        toggle.textContent = '−';
    }
}
</script>
