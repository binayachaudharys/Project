import { useMemo, useState } from 'react';

function lineKey(itemType, itemId) {
    return `${itemType}:${itemId}`;
}

/**
 * Local POS / invoice cart: lines, discount, optional VAT, editable unit prices.
 */
export default function usePosCart({
    vatEnabled = true,
    vatRate = 13,
    vatInclusive = false,
} = {}) {
    const [lines, setLines] = useState([]);
    const [discount, setDiscount] = useState(0);
    const [discountType, setDiscountType] = useState('amount');
    const [pricesIncludeVat, setPricesIncludeVat] = useState(vatInclusive);

    function addLine(sellable, itemType) {
        setLines((current) => {
            const key = lineKey(itemType, sellable.id);
            const existing = current.find(
                (line) => lineKey(line.item_type, line.item_id) === key,
            );

            if (existing) {
                return current.map((line) =>
                    lineKey(line.item_type, line.item_id) === key
                        ? { ...line, qty: line.qty + 1 }
                        : line,
                );
            }

            const price = Number(sellable.price);

            return [
                ...current,
                {
                    item_type: itemType,
                    item_id: sellable.id,
                    name: sellable.name,
                    catalog_price: price,
                    price,
                    qty: 1,
                },
            ];
        });
    }

    function updateQty(itemType, itemId, qty) {
        const safeQty = Math.max(1, Math.floor(Number(qty)) || 1);
        const key = lineKey(itemType, itemId);

        setLines((current) =>
            current.map((line) =>
                lineKey(line.item_type, line.item_id) === key
                    ? { ...line, qty: safeQty }
                    : line,
            ),
        );
    }

    function updatePrice(itemType, itemId, price) {
        const safePrice = Math.max(0, Number(price) || 0);
        const key = lineKey(itemType, itemId);

        setLines((current) =>
            current.map((line) =>
                lineKey(line.item_type, line.item_id) === key
                    ? { ...line, price: safePrice }
                    : line,
            ),
        );
    }

    function removeLine(itemType, itemId) {
        const key = lineKey(itemType, itemId);
        setLines((current) =>
            current.filter(
                (line) => lineKey(line.item_type, line.item_id) !== key,
            ),
        );
    }

    function clear() {
        setLines([]);
        setDiscount(0);
        setDiscountType('amount');
        setPricesIncludeVat(vatInclusive);
    }

    const subtotal = useMemo(
        () => lines.reduce((sum, line) => sum + line.price * line.qty, 0),
        [lines],
    );

    const discountAmount = useMemo(() => {
        const value = Number(discount) || 0;

        if (discountType === 'percent') {
            return Math.min(subtotal * (value / 100), subtotal);
        }

        return Math.min(value, subtotal);
    }, [subtotal, discount, discountType]);

    const net = Math.max(subtotal - discountAmount, 0);

    const { tax, total } = useMemo(() => {
        if (!vatEnabled || vatRate <= 0) {
            return { tax: 0, total: net };
        }

        if (pricesIncludeVat) {
            const taxable = net / (1 + vatRate / 100);
            const taxAmount = net - taxable;
            return { tax: taxAmount, total: net };
        }

        const taxAmount = net * (vatRate / 100);
        return { tax: taxAmount, total: net + taxAmount };
    }, [net, pricesIncludeVat, vatEnabled, vatRate]);

    return {
        lines,
        addLine,
        updateQty,
        updatePrice,
        removeLine,
        clear,
        discount,
        setDiscount,
        discountType,
        setDiscountType,
        pricesIncludeVat,
        setPricesIncludeVat,
        subtotal,
        discountAmount,
        tax,
        total,
    };
}
