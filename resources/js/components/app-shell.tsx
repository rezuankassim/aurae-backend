import { SidebarProvider } from '@/components/ui/sidebar';
import { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
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
        // Handle flash messages on initial full page load
        if (success) {
            toast.success(success);
        }
        if (error) {
            toast.error(error);
        }

        // Handle flash messages on subsequent Inertia visits
        // (router event fires every time, even if the message string is the same)
        const removeListener = router.on('success', (event) => {
            const props = event.detail.page.props as SharedData;
            if (props.success) {
                toast.success(props.success);
            }
            if (props.error) {
                toast.error(props.error);
            }
        });

        return removeListener;
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

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
