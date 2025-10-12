import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function slugify(text: string) {
    return text
        .toString()
        .toLowerCase()
        .trim()
        .replace(/[\s_]+/g, '-') // replace spaces & underscores with -
        .replace(/[^\w-]+/g, '') // remove non-word characters
        .replace(/--+/g, '-'); // collapse multiple dashes
}
