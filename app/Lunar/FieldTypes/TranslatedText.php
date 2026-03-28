<?php

namespace App\Lunar\FieldTypes;

use Filament\Forms\Components\Component;
use Lunar\Admin\Support\FieldTypes\TranslatedText as BaseTranslatedText;
use Lunar\Admin\Support\Forms\Components\TranslatedText as TranslatedTextComponent;
use Lunar\Models\Attribute;

class TranslatedText extends BaseTranslatedText
{
    public static function getFilamentComponent(Attribute $attribute): Component
    {
        return TranslatedTextComponent::make($attribute->handle)
            ->optionRichtext((bool) $attribute->configuration->get('richtext'))
            ->richtextFileAttachmentsDisk('public')
            ->richtextFileAttachmentsDirectory('editor-uploads')
            ->richtextFileAttachmentsVisibility('public')
            ->when(filled($attribute->validation_rules), fn (TranslatedTextComponent $component) => $component->rules($attribute->validation_rules))
            ->required((bool) $attribute->required)
            ->helperText($attribute->translate('description'));
    }
}
