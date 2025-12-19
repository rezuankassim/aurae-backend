import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Cart, type Country } from '@/types';
import { Head, useForm } from '@inertiajs/react';

interface Props {
    cart: Cart;
    countries: Country[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cart',
        href: '/cart',
    },
    {
        title: 'Checkout',
        href: '/checkout',
    },
];

export default function CheckoutIndex({ cart, countries }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        shipping_first_name: cart.shippingAddress?.first_name || '',
        shipping_last_name: cart.shippingAddress?.last_name || '',
        shipping_line_one: cart.shippingAddress?.line_one || '',
        shipping_line_two: cart.shippingAddress?.line_two || '',
        shipping_city: cart.shippingAddress?.city || '',
        shipping_state: cart.shippingAddress?.state || '',
        shipping_postcode: cart.shippingAddress?.postcode || '',
        shipping_country_id: cart.shippingAddress?.country_id || countries[0]?.id || 0,
        shipping_contact_email: cart.shippingAddress?.contact_email || '',
        shipping_contact_phone: cart.shippingAddress?.contact_phone || '',
        same_as_shipping: true,
        billing_first_name: '',
        billing_last_name: '',
        billing_line_one: '',
        billing_line_two: '',
        billing_city: '',
        billing_state: '',
        billing_postcode: '',
        billing_country_id: countries[0]?.id || 0,
        billing_contact_email: '',
        billing_contact_phone: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/checkout/address');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Checkout" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-3xl font-bold">Checkout</h1>
                    <p className="text-muted-foreground">Complete your purchase</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Shipping Address */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Shipping Address</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="shipping_first_name">First Name *</Label>
                                    <Input
                                        id="shipping_first_name"
                                        value={data.shipping_first_name}
                                        onChange={(e) => setData('shipping_first_name', e.target.value)}
                                        required
                                    />
                                    {errors.shipping_first_name && <p className="text-sm text-destructive">{errors.shipping_first_name}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="shipping_last_name">Last Name *</Label>
                                    <Input
                                        id="shipping_last_name"
                                        value={data.shipping_last_name}
                                        onChange={(e) => setData('shipping_last_name', e.target.value)}
                                        required
                                    />
                                    {errors.shipping_last_name && <p className="text-sm text-destructive">{errors.shipping_last_name}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="shipping_line_one">Address Line 1 *</Label>
                                <Input
                                    id="shipping_line_one"
                                    value={data.shipping_line_one}
                                    onChange={(e) => setData('shipping_line_one', e.target.value)}
                                    required
                                />
                                {errors.shipping_line_one && <p className="text-sm text-destructive">{errors.shipping_line_one}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="shipping_line_two">Address Line 2</Label>
                                <Input
                                    id="shipping_line_two"
                                    value={data.shipping_line_two}
                                    onChange={(e) => setData('shipping_line_two', e.target.value)}
                                />
                            </div>

                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="space-y-2">
                                    <Label htmlFor="shipping_city">City *</Label>
                                    <Input
                                        id="shipping_city"
                                        value={data.shipping_city}
                                        onChange={(e) => setData('shipping_city', e.target.value)}
                                        required
                                    />
                                    {errors.shipping_city && <p className="text-sm text-destructive">{errors.shipping_city}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="shipping_state">State/Province</Label>
                                    <Input
                                        id="shipping_state"
                                        value={data.shipping_state}
                                        onChange={(e) => setData('shipping_state', e.target.value)}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="shipping_postcode">Postal Code *</Label>
                                    <Input
                                        id="shipping_postcode"
                                        value={data.shipping_postcode}
                                        onChange={(e) => setData('shipping_postcode', e.target.value)}
                                        required
                                    />
                                    {errors.shipping_postcode && <p className="text-sm text-destructive">{errors.shipping_postcode}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="shipping_country_id">Country *</Label>
                                <Select
                                    value={data.shipping_country_id.toString()}
                                    onValueChange={(value) => setData('shipping_country_id', parseInt(value))}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {countries.map((country) => (
                                            <SelectItem key={country.id} value={country.id.toString()}>
                                                {country.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.shipping_country_id && <p className="text-sm text-destructive">{errors.shipping_country_id}</p>}
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="shipping_contact_email">Email *</Label>
                                    <Input
                                        id="shipping_contact_email"
                                        type="email"
                                        value={data.shipping_contact_email}
                                        onChange={(e) => setData('shipping_contact_email', e.target.value)}
                                        required
                                    />
                                    {errors.shipping_contact_email && <p className="text-sm text-destructive">{errors.shipping_contact_email}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="shipping_contact_phone">Phone *</Label>
                                    <Input
                                        id="shipping_contact_phone"
                                        value={data.shipping_contact_phone}
                                        onChange={(e) => setData('shipping_contact_phone', e.target.value)}
                                        required
                                    />
                                    {errors.shipping_contact_phone && <p className="text-sm text-destructive">{errors.shipping_contact_phone}</p>}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Billing Address */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Billing Address</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="same_as_shipping"
                                    checked={data.same_as_shipping}
                                    onCheckedChange={(checked) => setData('same_as_shipping', checked as boolean)}
                                />
                                <Label htmlFor="same_as_shipping" className="cursor-pointer">
                                    Same as shipping address
                                </Label>
                            </div>

                            {!data.same_as_shipping && (
                                <>
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="billing_first_name">First Name *</Label>
                                            <Input
                                                id="billing_first_name"
                                                value={data.billing_first_name}
                                                onChange={(e) => setData('billing_first_name', e.target.value)}
                                                required={!data.same_as_shipping}
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="billing_last_name">Last Name *</Label>
                                            <Input
                                                id="billing_last_name"
                                                value={data.billing_last_name}
                                                onChange={(e) => setData('billing_last_name', e.target.value)}
                                                required={!data.same_as_shipping}
                                            />
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="billing_line_one">Address Line 1 *</Label>
                                        <Input
                                            id="billing_line_one"
                                            value={data.billing_line_one}
                                            onChange={(e) => setData('billing_line_one', e.target.value)}
                                            required={!data.same_as_shipping}
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="billing_line_two">Address Line 2</Label>
                                        <Input
                                            id="billing_line_two"
                                            value={data.billing_line_two}
                                            onChange={(e) => setData('billing_line_two', e.target.value)}
                                        />
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-3">
                                        <div className="space-y-2">
                                            <Label htmlFor="billing_city">City *</Label>
                                            <Input
                                                id="billing_city"
                                                value={data.billing_city}
                                                onChange={(e) => setData('billing_city', e.target.value)}
                                                required={!data.same_as_shipping}
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="billing_state">State/Province</Label>
                                            <Input
                                                id="billing_state"
                                                value={data.billing_state}
                                                onChange={(e) => setData('billing_state', e.target.value)}
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="billing_postcode">Postal Code *</Label>
                                            <Input
                                                id="billing_postcode"
                                                value={data.billing_postcode}
                                                onChange={(e) => setData('billing_postcode', e.target.value)}
                                                required={!data.same_as_shipping}
                                            />
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="billing_country_id">Country *</Label>
                                        <Select
                                            value={data.billing_country_id.toString()}
                                            onValueChange={(value) => setData('billing_country_id', parseInt(value))}
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {countries.map((country) => (
                                                    <SelectItem key={country.id} value={country.id.toString()}>
                                                        {country.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    <div className="flex justify-end">
                        <Button type="submit" size="lg" disabled={processing}>
                            Continue to Review
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
