import { LexicalErrorBoundary } from '@lexical/react/LexicalErrorBoundary';
import { RichTextPlugin } from '@lexical/react/LexicalRichTextPlugin';
import { useState } from 'react';

import { ContentEditable } from '@/components/editor/editor-ui/content-editable';
import { ElementFormatToolbarPlugin } from '../editor/plugins/toolbar/element-format-toolbar-plugin';
import { ToolbarPlugin } from '../editor/plugins/toolbar/toolbar-plugin';

import { ClearEditorPlugin } from '@lexical/react/LexicalClearEditorPlugin';
import { ClickableLinkPlugin } from '@lexical/react/LexicalClickableLinkPlugin';
import { TabIndentationPlugin } from '@lexical/react/LexicalTabIndentationPlugin';
import { ActionsPlugin } from '../editor/plugins/actions/actions-plugin';
import { ClearEditorActionPlugin } from '../editor/plugins/actions/clear-editor-plugin';
import { AutoLinkPlugin } from '../editor/plugins/auto-link-plugin';
import { ImagesPlugin } from '../editor/plugins/images-plugin';
import { LinkPlugin } from '../editor/plugins/link-plugin';
import { BlockFormatDropDown } from '../editor/plugins/toolbar/block-format-toolbar-plugin';
import { FormatBulletedList } from '../editor/plugins/toolbar/block-format/format-bulleted-list';
import { FormatCheckList } from '../editor/plugins/toolbar/block-format/format-check-list';
import { FormatHeading } from '../editor/plugins/toolbar/block-format/format-heading';
import { FormatNumberedList } from '../editor/plugins/toolbar/block-format/format-numbered-list';
import { FormatParagraph } from '../editor/plugins/toolbar/block-format/format-paragraph';
import { FormatQuote } from '../editor/plugins/toolbar/block-format/format-quote';
import { BlockInsertPlugin } from '../editor/plugins/toolbar/block-insert-plugin';
import { InsertHorizontalRule } from '../editor/plugins/toolbar/block-insert/insert-horizontal-rule';
import { InsertImage } from '../editor/plugins/toolbar/block-insert/insert-image';
import { ClearFormattingToolbarPlugin } from '../editor/plugins/toolbar/clear-formatting-toolbar-plugin';
import { FloatingLinkEditorPlugin } from '../editor/plugins/toolbar/floating-link-editor-plugin';
import { FontBackgroundToolbarPlugin } from '../editor/plugins/toolbar/font-background-toolbar-plugin';
import { FontColorToolbarPlugin } from '../editor/plugins/toolbar/font-color-toolbar-plugin';
import { FontFormatToolbarPlugin } from '../editor/plugins/toolbar/font-format-toolbar-plugin';
import { FontSizeToolbarPlugin } from '../editor/plugins/toolbar/font-size-toolbar-plugin';
import { HistoryToolbarPlugin } from '../editor/plugins/toolbar/history-toolbar-plugin';
import { LinkToolbarPlugin } from '../editor/plugins/toolbar/link-toolbar-plugin';
import { SubSuperToolbarPlugin } from '../editor/plugins/toolbar/subsuper-toolbar-plugin';
import { Separator } from '../ui/separator';

export function Plugins() {
    const [floatingAnchorElem, setFloatingAnchorElem] = useState<HTMLDivElement | null>(null);

    const [isLinkEditMode, setIsLinkEditMode] = useState<boolean>(false);

    const onRef = (_floatingAnchorElem: HTMLDivElement) => {
        if (_floatingAnchorElem !== null) {
            setFloatingAnchorElem(_floatingAnchorElem);
        }
    };

    return (
        <div className="relative">
            {/* toolbar plugins */}
            <ToolbarPlugin>
                {({ blockType }) => (
                    <div className="vertical-align-middle sticky top-0 z-10 flex items-center gap-2 overflow-auto border-b p-1">
                        <HistoryToolbarPlugin />
                        <Separator orientation="vertical" className="!h-7" />
                        <BlockFormatDropDown>
                            <FormatParagraph />
                            <FormatHeading levels={['h1', 'h2', 'h3']} />
                            <FormatNumberedList />
                            <FormatBulletedList />
                            <FormatCheckList />
                            <FormatQuote />
                        </BlockFormatDropDown>
                        <FontSizeToolbarPlugin />
                        <Separator orientation="vertical" className="!h-7" />
                        <FontFormatToolbarPlugin format="bold" />
                        <FontFormatToolbarPlugin format="italic" />
                        <FontFormatToolbarPlugin format="underline" />
                        <FontFormatToolbarPlugin format="strikethrough" />
                        <Separator orientation="vertical" className="!h-7" />
                        <SubSuperToolbarPlugin />
                        <LinkToolbarPlugin setIsLinkEditMode={setIsLinkEditMode} />
                        <Separator orientation="vertical" className="!h-7" />
                        <ClearFormattingToolbarPlugin />
                        <Separator orientation="vertical" className="!h-7" />
                        <FontColorToolbarPlugin />
                        <FontBackgroundToolbarPlugin />
                        <Separator orientation="vertical" className="!h-7" />
                        <ElementFormatToolbarPlugin />
                        <Separator orientation="vertical" className="!h-7" />
                        <BlockInsertPlugin>
                            <InsertHorizontalRule />
                            <InsertImage />
                        </BlockInsertPlugin>
                    </div>
                )}
            </ToolbarPlugin>

            <div className="relative h-96">
                <RichTextPlugin
                    contentEditable={
                        <div className="">
                            <div className="" ref={onRef}>
                                <ContentEditable placeholder={'Start typing ...'} />
                            </div>
                        </div>
                    }
                    ErrorBoundary={LexicalErrorBoundary}
                />
                {/* editor plugins */}
                <TabIndentationPlugin />
                <ClickableLinkPlugin />
                <AutoLinkPlugin />
                <LinkPlugin />

                <ImagesPlugin />

                <FloatingLinkEditorPlugin anchorElem={floatingAnchorElem} isLinkEditMode={isLinkEditMode} setIsLinkEditMode={setIsLinkEditMode} />
            </div>
            {/* actions plugins */}
            <ActionsPlugin>
                <div className="clear-both flex items-center justify-between gap-2 overflow-auto border-t p-1">
                    <div className="flex flex-1 justify-start">{/* left side action buttons */}</div>
                    <div>{/* center action buttons */}</div>
                    <div className="flex flex-1 justify-end">
                        {/* right side action buttons */}
                        <>
                            <ClearEditorActionPlugin />
                            <ClearEditorPlugin />
                        </>
                    </div>
                </div>
            </ActionsPlugin>
        </div>
    );
}
