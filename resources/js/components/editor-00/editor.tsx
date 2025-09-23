'use client';

import { InitialConfigType, LexicalComposer } from '@lexical/react/LexicalComposer';
import { OnChangePlugin } from '@lexical/react/LexicalOnChangePlugin';
import { EditorState, SerializedEditorState } from 'lexical';

import { editorTheme } from '@/components/editor/themes/editor-theme';
import { TooltipProvider } from '@/components/ui/tooltip';

import { $generateHtmlFromNodes } from '@lexical/html';
import { nodes } from './nodes';
import { Plugins } from './plugins';

const editorConfig: InitialConfigType = {
    namespace: 'Editor',
    theme: editorTheme,
    nodes,
    onError: (error: Error) => {
        console.error(error);
    },
};

export function Editor({
    editorState,
    editorSerializedState,
    onChange,
    onSerializedChange,
    onChangeHtml,
}: {
    editorState?: EditorState;
    editorSerializedState?: SerializedEditorState;
    onChange?: (editorState: EditorState) => void;
    onSerializedChange?: (editorSerializedState: SerializedEditorState) => void;
    onChangeHtml?: (html: string) => void;
}) {
    return (
        <div className="overflow-hidden rounded-lg border bg-background shadow">
            <LexicalComposer
                initialConfig={{
                    ...editorConfig,
                    ...(editorState ? { editorState } : {}),
                    ...(editorSerializedState ? { editorState: JSON.stringify(editorSerializedState) } : {}),
                }}
            >
                <TooltipProvider>
                    <Plugins />

                    <OnChangePlugin
                        ignoreSelectionChange={true}
                        onChange={(editorState, editor) => {
                            editorState.read(() => {
                                onChangeHtml?.($generateHtmlFromNodes(editor, null));
                            });
                            onChange?.(editorState);
                            onSerializedChange?.(editorState.toJSON());
                        }}
                    />
                </TooltipProvider>
            </LexicalComposer>
        </div>
    );
}
