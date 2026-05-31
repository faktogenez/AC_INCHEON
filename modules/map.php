<?php
global $PROJECT_CONFIG, $lang;
$address = $PROJECT_CONFIG['address'];
$phone = $PROJECT_CONFIG['phone'];
$kakao_link = $PROJECT_CONFIG['map_links']['kakao'];
$naver_link = $PROJECT_CONFIG['map_links']['naver'];
?>
<div class="map-contacts">
    <div class="map-info">
        <h3><?php echo $lang == 'ru' ? 'Как нас найти' : '찾아오시는 길'; ?></h3>
        <p class="address">📍 <?php echo htmlspecialchars($address); ?></p>
        <div class="map-links">
            <a href="<?php echo $kakao_link; ?>" target="_blank" class="map-link kakao">🗺️ Kakao Maps</a>
            <a href="<?php echo $naver_link; ?>" target="_blank" class="map-link naver">🗺️ Naver Maps</a>
        </div>
        <div class="contact-simple">
            <p><?php echo $lang == 'ru' ? 'Телефон:' : '전화:'; ?> <a href="tel:<?php echo $phone; ?>"><?php echo $phone; ?></a></p>
        </div>
    </div>
</div>
