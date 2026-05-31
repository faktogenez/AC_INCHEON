<?php
global $PROJECT_CONFIG, $lang;
$address = $PROJECT_CONFIG['address'];
$phone = $PROJECT_CONFIG['phone'];
$kakao_link = $PROJECT_CONFIG['map_links']['kakao'];
$naver_link = $PROJECT_CONFIG['map_links']['naver'];
?>
<section class="section map-contacts" aria-labelledby="contacts-title">
    <div class="section-head">
        <h2 class="section-title" id="contacts-title"><?php echo $lang == 'ru' ? 'Как нас найти' : '찾아오시는 길'; ?></h2>
    </div>
    <div class="map-info">
        <p class="address"><?php echo htmlspecialchars($address); ?></p>
        <div class="map-links">
            <a href="<?php echo htmlspecialchars($kakao_link); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-ghost map-link kakao">Kakao Maps</a>
            <a href="<?php echo htmlspecialchars($naver_link); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-ghost map-link naver">Naver Maps</a>
        </div>
        <div class="contact-simple">
            <p><?php echo $lang == 'ru' ? 'Телефон:' : '전화:'; ?> <a href="tel:<?php echo htmlspecialchars($phone); ?>"><?php echo htmlspecialchars($phone); ?></a></p>
        </div>
    </div>
</section>
