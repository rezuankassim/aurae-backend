import { Check, ChevronsUpDown } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { useEffect, useRef, useState } from 'react';

const frameworks = [
    {
        value: 'next.js',
        label: 'Next.js',
    },
    {
        value: 'sveltekit',
        label: 'SvelteKit',
    },
    {
        value: 'nuxt.js',
        label: 'Nuxt.js',
    },
    {
        value: 'remix',
        label: 'Remix',
    },
    {
        value: 'astro',
        label: 'Astro',
    },
];

interface ComboboxProps {
    value: string | number | null;
    onValueChange: (value: string | number | null) => void;
    options: { label: string; value: string | number }[];
}

export function Combobox({ value, onValueChange, options }: ComboboxProps) {
    const buttonRef = useRef<HTMLDivElement>(null);
    const [open, setOpen] = useState(false);
    const [width, setWidth] = useState<number>();

    useEffect(() => {
        if (!buttonRef.current) {
            return;
        }

        const resizeObserver = new ResizeObserver((entries) => {
            setWidth(entries[0].contentRect.width);
        });

        resizeObserver.observe(buttonRef.current);

        return () => {
            resizeObserver.disconnect();
        };
    }, []);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <div className="relative w-full" ref={buttonRef}>
                <PopoverTrigger asChild>
                    <Button variant="outline" role="combobox" aria-expanded={open} className="w-full justify-between">
                        {value ? options.find((options) => options.value == value)?.label : 'Select options...'}
                        <ChevronsUpDown className="opacity-50" />
                    </Button>
                </PopoverTrigger>
            </div>
            <PopoverContent align="start" className="p-0" style={{ width }}>
                <Command>
                    <CommandInput placeholder="Search options..." className="h-9" />
                    <CommandList>
                        <CommandEmpty>No option found.</CommandEmpty>
                        <CommandGroup>
                            {options.map((option) => (
                                <CommandItem
                                    key={option.value}
                                    value={option.value.toString()}
                                    onSelect={(currentValue) => {
                                        onValueChange(currentValue === value ? '' : currentValue);
                                        setOpen(false);
                                    }}
                                >
                                    {option.label}
                                    <Check className={cn('ml-auto', value === option.value ? 'opacity-100' : 'opacity-0')} />
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
