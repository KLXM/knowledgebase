<?php

use FriendsOfREDAXO\Knowledgebase\AddonSettings;

$addon = rex_addon::get('knowledgebase');
echo rex_view::title(AddonSettings::getMenuTitle());
rex_be_controller::includeCurrentPageSubPath();