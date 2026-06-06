<?php

class rex_yform_value_knowledgebase_slug extends rex_yform_value_abstract
{
    public function enterObject(): void
    {
        $sourceField = (string) $this->getElement('source_field');
        if ($sourceField === '') {
            $sourceField = 'title';
        }
        $sourceValue = '';

        foreach ($this->params['values'] as $value) {
            if ($value->getName() == $sourceField) {
                $sourceValue = $value->getValue();
                break;
            }
        }

        $currentValue = (string) $this->getValue();

        if ($currentValue == '' && $sourceValue != '') {
            $currentValue = rex_string::normalize($sourceValue);
        }

        if ($currentValue != '') {
            $currentValue = rex_string::normalize($currentValue);
        }

        $this->setValue($currentValue);

        if ($this->needsOutput()) {
            $visibility = (string) $this->getElement('visibility');
            if ($visibility === '') {
                $visibility = 'hidden';
            }

            if ($visibility == 'hidden') {
                $this->params['form_output'][$this->getId()] = $this->parse('value.hidden.tpl.php');
            } elseif ($this->isViewable()) {
                if ($visibility == 'readonly') {
                    $attributes = $this->getElement('attributes');
                    if (!is_array($attributes)) {
                        $attributes = [];
                    }

                    $this->setElement('attributes', array_merge($attributes, ['readonly' => 'readonly']));
                    $this->params['form_output'][$this->getId()] = $this->parse('value.text.tpl.php');
                } else {
                    $this->params['form_output'][$this->getId()] = $this->parse('value.text.tpl.php');
                }
            }
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription(): string
    {
        return 'knowledgebase_slug|name|label|source_field_name|[no_db]';
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'knowledgebase_slug',
            'values' => [
                'name' => ['type' => 'name', 'label' => 'Feldname'],
                'label' => ['type' => 'text', 'label' => 'Bezeichnung'],
                'source_field' => ['type' => 'text', 'label' => 'Quell-Feld (z.B. title)'],
                'visibility' => ['type' => 'choice', 'label' => 'Sichtbarkeit', 'default' => 'hidden', 'choices' => 'sichtbar & editierbar=visible,sichtbar & schreibgeschuetzt=readonly,versteckt=hidden'],
                'no_db' => ['type' => 'no_db', 'label' => 'Datenbank', 'default' => 0],
            ],
            'description' => 'Generiert einen URL-Slug aus einem anderen Feld',
            'db_type' => ['varchar(191)'],
        ];
    }
}
