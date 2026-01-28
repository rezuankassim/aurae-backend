import HealthReportController from '@/actions/App/Http/Controllers/Admin/HealthReportController';
import { Combobox } from '@/components/combobox';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/health-reports';
import { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { FileText, X } from 'lucide-react';
import { useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Health Reports',
        href: index().url,
    },
    {
        title: 'Upload Report',
        href: '',
    },
];

interface User {
    id: number;
    name: string;
    email: string;
}

export default function HealthReportsCreate({ users }: { users: User[] }) {
    const fullBodyFileInputRef = useRef<HTMLInputElement>(null);
    const meridianFileInputRef = useRef<HTMLInputElement>(null);
    const multidimensionalFileInputRef = useRef<HTMLInputElement>(null);
    const [fullBodyFile, setFullBodyFile] = useState<File | null>(null);
    const [meridianFile, setMeridianFile] = useState<File | null>(null);
    const [multidimensionalFile, setMultidimensionalFile] = useState<File | null>(null);
    const [selectedUserId, setSelectedUserId] = useState<string | number | null>(null);

    const handleFileChange = (type: 'full_body' | 'meridian' | 'multidimensional') => (event: React.ChangeEvent<HTMLInputElement>) => {
        const files = event.target.files;
        if (files && files[0]) {
            if (type === 'full_body') setFullBodyFile(files[0]);
            else if (type === 'meridian') setMeridianFile(files[0]);
            else if (type === 'multidimensional') setMultidimensionalFile(files[0]);
        }
    };

    const handleRemoveFile = (type: 'full_body' | 'meridian' | 'multidimensional') => () => {
        if (type === 'full_body') {
            setFullBodyFile(null);
            if (fullBodyFileInputRef.current) fullBodyFileInputRef.current.value = '';
        } else if (type === 'meridian') {
            setMeridianFile(null);
            if (meridianFileInputRef.current) meridianFileInputRef.current.value = '';
        } else if (type === 'multidimensional') {
            setMultidimensionalFile(null);
            if (multidimensionalFileInputRef.current) multidimensionalFileInputRef.current.value = '';
        }
    };

    const handleBrowseClick = (ref: React.RefObject<HTMLInputElement>) => () => {
        ref.current?.click();
    };

    const formatFileSize = (bytes: number) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Upload Health Report" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Upload Health Report" description="Upload health reports for users" />
                </div>

                <Form
                    {...HealthReportController.store.form()}
                    options={{
                        preserveScroll: true,
                        onSuccess: () => {
                            setFullBodyFile(null);
                            setMeridianFile(null);
                            setMultidimensionalFile(null);
                            setSelectedUserId(null);
                            if (fullBodyFileInputRef.current) fullBodyFileInputRef.current.value = '';
                            if (meridianFileInputRef.current) meridianFileInputRef.current.value = '';
                            if (multidimensionalFileInputRef.current) multidimensionalFileInputRef.current.value = '';
                        },
                    }}
                    transform={(data) => ({
                        ...data,
                        user_id: selectedUserId,
                        full_body_file: fullBodyFile,
                        meridian_file: meridianFile,
                        multidimensional_file: multidimensionalFile,
                    })}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <Card className="mt-0">
                                <CardContent className="space-y-6">
                                    <div className="grid gap-2">
                                        <Label htmlFor="user_id">Select User</Label>

                                        <Combobox
                                            value={selectedUserId}
                                            onValueChange={setSelectedUserId}
                                            options={users.map((user) => ({
                                                value: user.id,
                                                label: `${user.name} (${user.email})`,
                                            }))}
                                        />

                                        <InputError message={errors.user_id} />
                                    </div>

                                    {/* Full Body Health Report */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="full_body_file">Full Body Health Report (全身健康评估报告): PDF</Label>

                                        <input
                                            ref={fullBodyFileInputRef}
                                            type="file"
                                            name="full_body_file"
                                            id="full_body_file"
                                            accept=".pdf"
                                            onChange={handleFileChange('full_body')}
                                            className="hidden"
                                        />

                                        <div className="space-y-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={handleBrowseClick(fullBodyFileInputRef)}
                                                className="w-full"
                                            >
                                                <FileText className="mr-2 h-4 w-4" />
                                                Browse File
                                            </Button>

                                            {fullBodyFile && (
                                                <div className="flex items-center justify-between rounded-md border bg-muted/50 p-2">
                                                    <div className="flex items-center gap-2">
                                                        <FileText className="h-4 w-4 text-muted-foreground" />
                                                        <div className="flex flex-col">
                                                            <span className="text-sm font-medium">{fullBodyFile.name}</span>
                                                            <span className="text-xs text-muted-foreground">
                                                                {formatFileSize(fullBodyFile.size)}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={handleRemoveFile('full_body')}
                                                        className="h-8 w-8"
                                                    >
                                                        <X className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            )}
                                        </div>

                                        <InputError message={errors.full_body_file} />
                                    </div>

                                    {/* Meridian Health Report */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="meridian_file">Meridian Health Report (经络健康评估报告): PDF</Label>

                                        <input
                                            ref={meridianFileInputRef}
                                            type="file"
                                            name="meridian_file"
                                            id="meridian_file"
                                            accept=".pdf"
                                            onChange={handleFileChange('meridian')}
                                            className="hidden"
                                        />

                                        <div className="space-y-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={handleBrowseClick(meridianFileInputRef)}
                                                className="w-full"
                                            >
                                                <FileText className="mr-2 h-4 w-4" />
                                                Browse File
                                            </Button>

                                            {meridianFile && (
                                                <div className="flex items-center justify-between rounded-md border bg-muted/50 p-2">
                                                    <div className="flex items-center gap-2">
                                                        <FileText className="h-4 w-4 text-muted-foreground" />
                                                        <div className="flex flex-col">
                                                            <span className="text-sm font-medium">{meridianFile.name}</span>
                                                            <span className="text-xs text-muted-foreground">
                                                                {formatFileSize(meridianFile.size)}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={handleRemoveFile('meridian')}
                                                        className="h-8 w-8"
                                                    >
                                                        <X className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            )}
                                        </div>

                                        <InputError message={errors.meridian_file} />
                                    </div>

                                    {/* Multidimensional Health Report */}
                                    <div className="grid gap-2">
                                        <Label htmlFor="multidimensional_file">
                                            Multidimensional Health Report (多维健康评估报告): PDF
                                        </Label>

                                        <input
                                            ref={multidimensionalFileInputRef}
                                            type="file"
                                            name="multidimensional_file"
                                            id="multidimensional_file"
                                            accept=".pdf"
                                            onChange={handleFileChange('multidimensional')}
                                            className="hidden"
                                        />

                                        <div className="space-y-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={handleBrowseClick(multidimensionalFileInputRef)}
                                                className="w-full"
                                            >
                                                <FileText className="mr-2 h-4 w-4" />
                                                Browse File
                                            </Button>

                                            {multidimensionalFile && (
                                                <div className="flex items-center justify-between rounded-md border bg-muted/50 p-2">
                                                    <div className="flex items-center gap-2">
                                                        <FileText className="h-4 w-4 text-muted-foreground" />
                                                        <div className="flex flex-col">
                                                            <span className="text-sm font-medium">{multidimensionalFile.name}</span>
                                                            <span className="text-xs text-muted-foreground">
                                                                {formatFileSize(multidimensionalFile.size)}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={handleRemoveFile('multidimensional')}
                                                        className="h-8 w-8"
                                                    >
                                                        <X className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            )}
                                        </div>

                                        <InputError message={errors.multidimensional_file} />
                                    </div>

                                    {/* General files error */}
                                    <InputError message={errors.files} />
                                </CardContent>
                            </Card>

                            <div className="flex gap-2">
                                <Button
                                    type="submit"
                                    disabled={processing || (!fullBodyFile && !meridianFile && !multidimensionalFile)}
                                >
                                    Upload Report{(fullBodyFile ? 1 : 0) + (meridianFile ? 1 : 0) + (multidimensionalFile ? 1 : 0) > 1 ? 's' : ''}
                                </Button>

                                <Button type="button" variant="outline" asChild>
                                    <Link href={index().url}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
