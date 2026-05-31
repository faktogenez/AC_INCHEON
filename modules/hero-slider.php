<?php
global $PROJECT_CONFIG, $lang;

$slides = $PROJECT_CONFIG['slides'] ?? [];
if (empty($slides)) {
    echo '<div class="hero-placeholder">Настройте слайды в config.php</div>';
    return;
}
?>
<div class="swiper hero-slider">
   <h2 class="section-title"><?php echo $lang == 'ru' ? 'Наши услуги' : '서비스'; ?></h2>
    <div class="swiper-wrapper">
        <?php foreach ($slides as $slide): ?>
            <div class="swiper-slide hero-slide" style="background-image: url('<?php echo htmlspecialchars($slide['image']); ?>');">
                <div class="hero-content">
                    <h2><?php echo htmlspecialchars($slide['title_' . $lang]); ?></h2>
                    <p><?php echo htmlspecialchars($slide['desc_' . $lang]); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="swiper-pagination"></div>
</div>
