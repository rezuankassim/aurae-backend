import { Progress } from '@/components/ui/progress';

interface UploadProgressProps {
    progress: number;
    isUploading: boolean;
}

export default function UploadProgress({ progress, isUploading }: UploadProgressProps) {
    if (!isUploading) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/80 backdrop-blur-sm">
            <div className="w-full max-w-md space-y-4 rounded-lg border bg-card p-6 shadow-lg">
                <div className="space-y-2">
                    <div className="flex items-center justify-between">
                        <h3 className="text-lg font-semibold">Uploading File</h3>
                        <span className="text-sm text-muted-foreground">{progress}%</span>
                    </div>
                    <Progress value={progress} className="h-2" />
                    <p className="text-sm text-muted-foreground">Please do not close or refresh this page while the upload is in progress.</p>
                </div>
            </div>
        </div>
    );
}
