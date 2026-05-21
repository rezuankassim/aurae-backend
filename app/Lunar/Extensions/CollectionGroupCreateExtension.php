<?php

namespace App\Lunar\Extensions;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Support\Str;
use Lunar\Admin\Support\Extending\CreatePageExtension;

class CollectionGroupCreateExtension extends CreatePageExtension
{
    public function extendForm(Form $form): Form
    {
        $schema = $form->getComponents(withHidden: true);
        $this->hideHandleField($schema);

        return $form->schema($schema);
    }

    public function beforeCreate(array $data): array
    {
        $data['handle'] = Str::slug($data['name']);

        return $data;
    }

    protected function hideHandleField(array $components): void
    {
        foreach ($components as $component) {
            if ($component instanceof TextInput && $component->getName() === 'handle') {
                $component->hidden(true);
            }

            if (method_exists($component, 'getChildComponents')) {
                $this->hideHandleField($component->getChildComponents());
            }
        }
    }
}
