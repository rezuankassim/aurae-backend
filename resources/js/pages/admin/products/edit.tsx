import AppLayout from '@/layouts/app-layout';
import { Product, ProductType, Tag, type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/products';

import { Editor } from '@/components/editor-00/editor';
import { SerializedEditorState, SerializedLexicalNode } from 'lexical';
import { CheckIcon } from 'lucide-react';
import { SetStateAction, useState } from 'react';

import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import HeadingSmall from '@/components/heading-small';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tags, TagsContent, TagsEmpty, TagsGroup, TagsInput, TagsItem, TagsList, TagsTrigger, TagsValue } from '@/components/ui/tags';
import ProductsLayout from '@/layouts/products/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: index().url,
    },
    {
        title: 'Edit products',
        href: '',
    },
];

export default function EditProducts({ product, productTypes, tags }: { product: Product; productTypes: ProductType[]; tags: Tag[] }) {
    const initialValue = JSON.parse(product.attribute_data.ori_description.en) as unknown as SerializedEditorState;
    const [editorState, setEditorState] = useState<SerializedEditorState>(initialValue);
    const [editorHtmlState, setEditorHtmlState] = useState<string>('');

    const [selectedTags, setSelectedTags] = useState<string[]>(product.tags_array.map((tag) => String(tag)));

    const handleRemove = (value: string) => {
        if (!selectedTags.includes(value)) {
            return;
        }

        setSelectedTags((prev) => prev.filter((v) => v !== value));
    };

    const handleSelect = (value: string) => {
        if (selectedTags.includes(value)) {
            handleRemove(value);
            return;
        }

        setSelectedTags((prev) => [...prev, value]);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit product" />

            <ProductsLayout id_record={product.id}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                    <HeadingSmall title="Edit product" description="Manage system's product, edit or publish" />

                    <Form
                        {...ProductController.update.form(product.id)}
                        options={{
                            preserveScroll: true,
                        }}
                        resetOnSuccess
                        transform={(data) => ({
                            ...data,
                            content: JSON.stringify(editorState),
                            html_content: editorHtmlState,
                        })}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <Card className="mt-0">
                                    <CardContent className="space-y-6">
                                        <div className="grid gap-2">
                                            <Label htmlFor="product_type">Product Type</Label>

                                            <Select name="type" defaultValue={String(product.product_type_id)}>
                                                <SelectTrigger id="product_type" name="product_type">
                                                    <SelectValue placeholder="Select product type" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {productTypes.map((type) => (
                                                        <SelectItem key={type.id} value={String(type.id)}>
                                                            {type.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>

                                            <InputError message={errors.type} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="tags">Tags</Label>

                                            <Tags>
                                                <TagsTrigger id="tags" name="tags">
                                                    {selectedTags.map((tag) => (
                                                        <TagsValue key={tag} onRemove={() => handleRemove(String(tag))}>
                                                            {String(tags.find((t) => t.id === Number(tag))?.value || '')}
                                                        </TagsValue>
                                                    ))}
                                                </TagsTrigger>
                                                <TagsContent>
                                                    <TagsInput placeholder="Search tag..." />
                                                    <TagsList>
                                                        <TagsEmpty />
                                                        <TagsGroup>
                                                            {tags.map((tag) => (
                                                                <TagsItem key={tag.id} onSelect={handleSelect} value={String(tag.id)}>
                                                                    {tag.value}
                                                                    {selectedTags.includes(String(tag.id)) && (
                                                                        <CheckIcon className="text-muted-foreground" size={14} />
                                                                    )}
                                                                </TagsItem>
                                                            ))}
                                                        </TagsGroup>
                                                    </TagsList>
                                                </TagsContent>
                                            </Tags>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardContent className="space-y-6">
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">Name</Label>

                                            <Input id="name" name="name" placeholder="Name" defaultValue={product.attribute_data.name.en} />

                                            <InputError message={errors.name} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="description">Description</Label>

                                            <Editor
                                                editorSerializedState={editorState}
                                                onSerializedChange={(value: SetStateAction<SerializedEditorState<SerializedLexicalNode>>) =>
                                                    setEditorState(value)
                                                }
                                                onChangeHtml={(html) => setEditorHtmlState(html)}
                                            />

                                            <InputError message={errors.description} />
                                        </div>
                                    </CardContent>
                                </Card>

                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        Submit
                                    </Button>

                                    <Button type="button" variant="outline" asChild>
                                        <Link href={index().url}>Cancel</Link>
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </ProductsLayout>
        </AppLayout>
    );
}
