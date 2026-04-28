import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { ConnectionState } from '@/hooks/use-admin-notifications';
import { cn } from '@/lib/utils';
import { Loader2, Wifi, WifiOff } from 'lucide-react';

type StatusConfig = {
    label: string;
    description: string;
    icon: typeof Wifi;
    iconClass: string;
    spin?: boolean;
};

const STATUS_CONFIG: Record<ConnectionState, StatusConfig> = {
    connected: {
        label: 'Connected',
        description: 'Real-time alerts are active.',
        icon: Wifi,
        iconClass: 'text-emerald-500',
    },
    connecting: {
        label: 'Connecting…',
        description: 'Establishing connection to real-time alerts.',
        icon: Loader2,
        iconClass: 'text-amber-500',
        spin: true,
    },
    initialized: {
        label: 'Connecting…',
        description: 'Establishing connection to real-time alerts.',
        icon: Loader2,
        iconClass: 'text-amber-500',
        spin: true,
    },
    unavailable: {
        label: 'Reconnecting…',
        description: 'Connection unavailable. Retrying in the background.',
        icon: Loader2,
        iconClass: 'text-amber-500',
        spin: true,
    },
    failed: {
        label: 'Disconnected',
        description: 'Real-time alerts are unavailable. Refresh the page to retry.',
        icon: WifiOff,
        iconClass: 'text-destructive',
    },
    disconnected: {
        label: 'Disconnected',
        description: 'Real-time alerts are not active.',
        icon: WifiOff,
        iconClass: 'text-destructive',
    },
    disabled: {
        label: 'Disabled',
        description: 'Real-time alerts are disabled.',
        icon: WifiOff,
        iconClass: 'text-muted-foreground',
    },
};

export function RealtimeStatusIndicator({ connectionState }: { connectionState: ConnectionState }) {
    const config = STATUS_CONFIG[connectionState];
    const Icon = config.icon;

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8 cursor-default"
                    aria-label={`Real-time alerts: ${config.label}`}
                >
                    <Icon className={cn('h-4 w-4', config.iconClass, config.spin && 'animate-spin')} />
                </Button>
            </TooltipTrigger>
            <TooltipContent side="bottom">
                <div className="flex flex-col gap-0.5">
                    <span className="font-medium">Real-time alerts: {config.label}</span>
                    <span className="text-[11px] opacity-80">{config.description}</span>
                </div>
            </TooltipContent>
        </Tooltip>
    );
}
