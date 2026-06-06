<?php

$mode = (string) $this->getVar('mode', 'open');
$enableSection = (bool) $this->getVar('enable_section', false);
$enableContainer = (bool) $this->getVar('enable_container', false);

if ('close' === $mode) {
    $sectionBgImage = trim((string) $this->getVar('section_bg_image', ''));
    $bgMediaExt = strtolower(pathinfo($sectionBgImage, PATHINFO_EXTENSION));
    $hasBackgroundVideo = '' !== $sectionBgImage && in_array($bgMediaExt, ['mp4', 'webm', 'ogg'], true);

    if ($enableContainer) {
        echo '</div>';
    }

    if ($enableSection && $hasBackgroundVideo) {
        echo '</div>';
    }

    if ($enableSection) {
        echo '</section>';
    }

    return;
}

$sectionClasses = [];
if ($enableSection) {
    $sectionClasses[] = 'uk-section';

    $sectionBg = trim((string) $this->getVar('section_bg', ''));
    if ('' !== $sectionBg) {
        $sectionClasses[] = $sectionBg;
    }

    $sectionPadding = trim((string) $this->getVar('section_padding', ''));
    if ('' !== $sectionPadding) {
        $sectionClasses[] = $sectionPadding;
    }

    if ((bool) $this->getVar('section_light', false)) {
        $sectionClasses[] = 'uk-light';
    }
}

$sectionBgImage = trim((string) $this->getVar('section_bg_image', ''));
$sectionStyle = '';
$backgroundVideoHtml = '';
$hasBackgroundVideo = false;

if ($enableSection && '' !== $sectionBgImage) {
    $bgMediaExt = strtolower(pathinfo($sectionBgImage, PATHINFO_EXTENSION));
    $videoExtensions = ['mp4', 'webm', 'ogg'];

    if (in_array($bgMediaExt, $videoExtensions, true)) {
        $hasBackgroundVideo = true;
        $sectionClasses[] = 'uk-cover-container';
        $sectionClasses[] = 'uk-position-relative';
        $videoSrc = rex_url::media($sectionBgImage);
        $backgroundVideoHtml = '<video class="uk-cover" autoplay loop muted playsinline uk-cover><source src="'
            . rex_escape($videoSrc)
            . '" type="video/'
            . rex_escape($bgMediaExt)
            . '"></video>';
    } else {
        $sectionClasses[] = 'uk-background-cover';
        $bgImageUrl = rex_media_manager::getUrl('content_slideshow', $sectionBgImage);
        $sectionStyle = ' style="background-image: url(\'' . rex_escape($bgImageUrl) . '\'); background-size: cover; background-position: center;"';
    }
}

if ($enableSection) {
    echo '<section class="' . rex_escape(implode(' ', $sectionClasses)) . '"' . $sectionStyle . '>';
    if ($hasBackgroundVideo) {
        echo $backgroundVideoHtml;
        echo '<div class="uk-position-relative">';
    }
}

if ($enableContainer) {
    $containerWidth = trim((string) $this->getVar('container_width', 'uk-container'));
    if ('' !== $containerWidth) {
        echo '<div class="' . rex_escape($containerWidth) . '">';
    }
}