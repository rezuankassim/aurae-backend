<?php

namespace App\Lunar\Extensions;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Lunar\Admin\Support\Extending\CreatePageExtension;

class CollectionGroupCreateExtension extends CreatePageExtension
{
    public function extendForm(Form $form): Form
    {
        $schema = $form->getComponents(withHidden: true);
        $this->hideHandleField($schema);

        return $form->schema($schema);
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
