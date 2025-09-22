import { SidebarProvider } from '@/components/ui/sidebar';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { Toaster } from './ui/sonner';

interface AppShellProps {
    children: React.ReactNode;
    variant?: 'header' | 'sidebar';
}

export function AppShell({ children, variant = 'header' }: AppShellProps) {
    const isOpen = usePage<SharedData>().props.sidebarOpen;
    const success = usePage<SharedData>().props.success;
    const error = usePage<SharedData>().props.error;

    useEffect(() => {
        if (success) {
            toast.success(success);
        }

        if (error) {
            toast.error(error);
        }
    }, [success, error]);

    if (variant === 'header') {
        return (
            <>
                <div className="flex min-h-screen w-full flex-col">{children}</div>
                <Toaster position="top-right" closeButton />
            </>
        );
    }

    return (
        <SidebarProvider defaultOpen={isOpen}>
            {children}
            <Toaster position="top-right" closeButton />
        </SidebarProvider>
    );
}
