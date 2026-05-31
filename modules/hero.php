<?php
// Модуль: Hero (Главный блок с именем, специализацией и телефоном)
global $PROJECT_CONFIG, $lang;
?>
<div class="hero">
    <div class="hero-content">
        <h1><?php echo htmlspecialchars($PROJECT_CONFIG['site_name'][$lang]); ?></h1>
        <p class="hero-subtitle"><?php echo htmlspecialchars($PROJECT_CONFIG['site_subtitle'][$lang]); ?></p>
        <a href="tel:<?php echo $PROJECT_CONFIG['phone']; ?>" class="hero-button"><?php echo $lang == 'ru' ? 'Заказать звонок' : '전화 문의'; ?></a>
    </div>
</div>
