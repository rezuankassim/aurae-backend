import LegalSettingController from '@/actions/App/Http/Controllers/Admin/LegalSettingController';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import AppLayout from '@/layouts/app-layout';
import { edit } from '@/routes/admin/legal-settings';
import { BreadcrumbItem, GeneralSetting } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { SerializedEditorState, SerializedLexicalNode } from 'lexical';
import { SetStateAction, useState } from 'react';

import { Editor } from '@/components/editor-00/editor';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Legal Settings',
        href: edit().url,
    },
];

const defaultEditorState = {
    root: {
        children: [
            {
                children: [],
                direction: 'ltr',
                format: '',
                indent: 0,
                type: 'paragraph',
                version: 1,
            },
        ],
        direction: 'ltr',
        format: '',
        indent: 0,
        type: 'root',
        version: 1,
    },
} as unknown as SerializedEditorState;

export default function LegalSettingsEdit({ generalSetting }: { generalSetting: GeneralSetting }) {
    // Terms and Conditions state
    const termsInitialValue = generalSetting.terms_and_conditions_content
        ? (JSON.parse(generalSetting.terms_and_conditions_content) as unknown as SerializedEditorState)
        : defaultEditorState;
    const [termsEditorState, setTermsEditorState] = useState<SerializedEditorState>(termsInitialValue);
    const [termsHtmlState, setTermsHtmlState] = useState<string>(generalSetting.terms_and_conditions_html || '');

    // Privacy Policy state
    const privacyInitialValue = generalSetting.privacy_policy_content
        ? (JSON.parse(generalSetting.privacy_policy_content) as unknown as SerializedEditorState)
        : defaultEditorState;
    const [privacyEditorState, setPrivacyEditorState] = useState<SerializedEditorState>(privacyInitialValue);
    const [privacyHtmlState, setPrivacyHtmlState] = useState<string>(generalSetting.privacy_policy_html || '');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Legal Settings" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <HeadingSmall title="Legal Settings" description="Manage terms and conditions & privacy policy" />

                <Form
                    {...LegalSettingController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    transform={(data) => ({
                        ...data,
                        terms_and_conditions_content: JSON.stringify(termsEditorState),
                        terms_and_conditions_html: termsHtmlState,
                        privacy_policy_content: JSON.stringify(privacyEditorState),
                        privacy_policy_html: privacyHtmlState,
                    })}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <Card>
                                <CardContent className="space-y-6">
                                    <div className="space-y-2">
                                        <h3 className="text-lg font-medium">Terms and Conditions</h3>
                                        <p className="text-sm text-muted-foreground">
                                            Define your platform's terms and conditions. This will be publicly accessible at /terms-and-conditions
                                        </p>
                                    </div>

                                    <Field>
                                        <FieldLabel htmlFor="terms_content">Content</FieldLabel>
                                        <Editor
                                            editorSerializedState={termsEditorState}
                                            onSerializedChange={(value: SetStateAction<SerializedEditorState<SerializedLexicalNode>>) =>
                                                setTermsEditorState(value)
                                            }
                                            onChangeHtml={(html) => setTermsHtmlState(html)}
                                        />

                                        {errors.terms_and_conditions_content || errors.terms_and_conditions_html ? (
                                            <FieldError>{errors.terms_and_conditions_content || errors.terms_and_conditions_html}</FieldError>
                                        ) : null}
                                    </Field>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="space-y-6">
                                    <div className="space-y-2">
                                        <h3 className="text-lg font-medium">Privacy Policy</h3>
                                        <p className="text-sm text-muted-foreground">
                                            Define your platform's privacy policy. This will be publicly accessible at /privacy-policy
                                        </p>
                                    </div>

                                    <Field>
                                        <FieldLabel htmlFor="privacy_content">Content</FieldLabel>
                                        <Editor
                                            editorSerializedState={privacyEditorState}
                                            onSerializedChange={(value: SetStateAction<SerializedEditorState<SerializedLexicalNode>>) =>
                                                setPrivacyEditorState(value)
                                            }
                                            onChangeHtml={(html) => setPrivacyHtmlState(html)}
                                        />

                                        {errors.privacy_policy_content || errors.privacy_policy_html ? (
                                            <FieldError>{errors.privacy_policy_content || errors.privacy_policy_html}</FieldError>
                                        ) : null}
                                    </Field>
                                </CardContent>
                            </Card>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Save Changes
                                </Button>

                                <Button type="button" variant="outline" asChild>
                                    <Link href={edit().url}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
