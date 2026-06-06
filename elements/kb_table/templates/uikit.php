<?php
/**
 * KB Tabelle - UIkit Template
 * @var array $elementData
 */

$tableData = (string) ($elementData['table_data'] ?? '');
$tableCaption = '';
$tableStyle = (string) ($elementData['table_style'] ?? 'default');
$tableSize = (string) ($elementData['table_size'] ?? 'default');
$tableHover = !empty($elementData['table_hover']);
$tableResponsive = (string) ($elementData['table_responsive'] ?? '');
$tableAlign = (string) ($elementData['table_align'] ?? '');

$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionBgImage = (string) ($elementData['section_bg_image'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight = !empty($elementData['section_light']);
$enableSection = !empty($elementData['enable_section']);
$enableContainer = !empty($elementData['enable_container']);

$tableRows = [];
$tableHeadRows = [];
$tableCols = [];
$hasHeaderCol = false;

$data = json_decode($tableData, true);
if (is_array($data) && isset($data['rows'])) {
    $rows = is_array($data['rows']) ? $data['rows'] : [];
    $hasHeaderRow = !empty($data['has_header_row']);
    $hasHeaderCol = !empty($data['has_header_col']);
    $tableCols = is_array($data['cols'] ?? null) ? $data['cols'] : [];

    if (isset($data['caption']) && is_string($data['caption'])) {
        $tableCaption = $data['caption'];
    }

    $normalizedRows = [];
    foreach ($rows as $row) {
        if (is_array($row)) {
            $normalizedRows[] = array_values($row);
        }
    }

    if ($hasHeaderRow && $normalizedRows !== []) {
        $tableHeadRows[] = array_shift($normalizedRows);
    }

    $tableRows = $normalizedRows;
}

if ($tableHeadRows === [] && $tableRows === []) {
    return;
}

$tableClasses = ['uk-table'];
if ($tableStyle !== 'default') {
    $tableClasses = array_merge($tableClasses, explode(' ', $tableStyle));
}
if ($tableSize !== 'default') {
    $tableClasses[] = $tableSize;
}
if ($tableHover) {
    $tableClasses[] = 'uk-table-hover';
}
if ($tableResponsive !== '') {
    $tableClasses[] = $tableResponsive;
}
if ($tableAlign !== '') {
    $tableClasses[] = $tableAlign;
}
$tableClasses = array_values(array_unique($tableClasses));
$tableClassStr = implode(' ', $tableClasses);

$alignStyle = static function (string $type): string {
    if ($type === 'number') {
        return ' style="text-align:right;"';
    }

    if ($type === 'center') {
        return ' style="text-align:center;"';
    }

    return '';
};

$wrapper = new rex_fragment();
$wrapper->setVar('enable_section', $enableSection, false);
$wrapper->setVar('enable_container', $enableContainer, false);
$wrapper->setVar('section_bg', $sectionBg, false);
$wrapper->setVar('section_bg_image', $sectionBgImage, false);
$wrapper->setVar('section_padding', $sectionPadding, false);
$wrapper->setVar('container_width', $containerWidth, false);
$wrapper->setVar('section_light', $sectionLight, false);

$wrapperClose = new rex_fragment();
$wrapperClose->setVar('mode', 'close', false);
$wrapperClose->setVar('enable_section', $enableSection, false);
$wrapperClose->setVar('enable_container', $enableContainer, false);
$wrapperClose->setVar('section_bg_image', $sectionBgImage, false);
?>
<?= $wrapper->parse('kb_elements/wrapper.php') ?>

<?php if ($tableResponsive === ''): ?>
<div class="uk-overflow-auto">
<?php endif; ?>

<table class="<?= rex_escape($tableClassStr) ?>">
    <?php if ($tableCaption !== ''): ?>
    <caption><?= rex_escape($tableCaption) ?></caption>
    <?php endif; ?>

    <?php if ($tableHeadRows !== []): ?>
    <thead>
        <?php foreach ($tableHeadRows as $row): ?>
        <tr>
            <?php foreach ($row as $cellIndex => $cell): ?>
            <?php
            $colDef = is_array($tableCols[$cellIndex] ?? null) ? $tableCols[$cellIndex] : [];
            $headerType = (string) ($colDef['header_type'] ?? ($colDef['type'] ?? 'text'));
            ?>
            <th scope="col"<?= $alignStyle($headerType) ?>><?= rex_escape((string) $cell) ?></th>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </thead>
    <?php endif; ?>

    <?php if ($tableRows !== []): ?>
    <tbody>
        <?php foreach ($tableRows as $row): ?>
        <tr>
            <?php foreach ($row as $cellIndex => $cell): ?>
            <?php
            $colDef = is_array($tableCols[$cellIndex] ?? null) ? $tableCols[$cellIndex] : [];
            $bodyType = (string) ($colDef['type'] ?? 'text');
            ?>
            <?php if ($hasHeaderCol && $cellIndex === 0): ?>
            <th scope="row"<?= $alignStyle($bodyType) ?>><?= rex_escape((string) $cell) ?></th>
            <?php else: ?>
            <td<?= $alignStyle($bodyType) ?>><?= rex_escape((string) $cell) ?></td>
            <?php endif; ?>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <?php endif; ?>
</table>

<?php if ($tableResponsive === ''): ?>
</div>
<?php endif; ?>

<?= $wrapperClose->parse('kb_elements/wrapper.php') ?>
