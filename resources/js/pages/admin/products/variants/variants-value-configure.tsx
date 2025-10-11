import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sortable, SortableContent, SortableItem, SortableItemHandle, SortableOverlay } from '@/components/ui/sortable';
import { ProductOptionValue } from '@/types';
import { GripVertical, Trash2 } from 'lucide-react';
import { useState } from 'react';

export default function VariantsValueConfigure({ option, value }: { option: number; value: ProductOptionValue[] }) {
    const [valuesOrder, setValuesOrder] = useState(value);

    const deleteValue = (id: number) => {
        setValuesOrder((prev) => prev.filter((val) => val.id !== id));
    };

    const addValue = () => {
        const newId = valuesOrder.length > 0 ? Math.max(...valuesOrder.map((val) => val.id)) + 1 : 1;
        const newValue = {
            id: newId,
            name: {
                en: '',
            },
        } as ProductOptionValue;
        setValuesOrder((prev) => [...prev, newValue]);
    };

    return (
        <Sortable value={valuesOrder} onValueChange={setValuesOrder} getItemValue={(item) => item.id}>
            <SortableContent asChild>
                <div className="grid gap-2">
                    <Label htmlFor={`${option}-value`}>Values</Label>
                    {valuesOrder.map((val) => (
                        <SortableItem key={val.id} value={val.id} asChild>
                            <div className="flex items-center gap-2">
                                <SortableItemHandle asChild>
                                    <Button variant="ghost" size="icon" className="size-8">
                                        <GripVertical className="h-4 w-4" />
                                    </Button>
                                </SortableItemHandle>

                                <Input
                                    id={`${option}-value-${val.id}`}
                                    name={`${option}-value-${val.id}`}
                                    placeholder="Value"
                                    defaultValue={val.name.en}
                                />

                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="p-4 text-destructive-foreground"
                                    onClick={() => deleteValue(val.id)}
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </div>
                        </SortableItem>
                    ))}
                    <div className="flex items-center justify-center">
                        <Button type="button" variant="outline" size="sm" className="w-fit" onClick={addValue}>
                            Add value
                        </Button>
                    </div>
                </div>
            </SortableContent>
            <SortableOverlay>
                <div className="size-full rounded-none bg-primary/10" />
            </SortableOverlay>
        </Sortable>
    );
}
