import { AutoLinkNode, LinkNode } from '@lexical/link';
import { HeadingNode, QuoteNode } from '@lexical/rich-text';
import { Klass, LexicalNode, LexicalNodeReplacement, ParagraphNode, TextNode } from 'lexical';
import { ImageNode } from '../editor/nodes/image-node';

export const nodes: ReadonlyArray<Klass<LexicalNode> | LexicalNodeReplacement> = [
    HeadingNode,
    ParagraphNode,
    TextNode,
    QuoteNode,
    AutoLinkNode,
    LinkNode,
    ImageNode,
];
