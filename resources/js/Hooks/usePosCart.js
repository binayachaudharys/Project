import { useMemo, useState } from 'react';

function lineKey(itemType, itemId) {
    return `${itemType}:${itemId}`;
}

/**
 * Local POS cart state: line items, discount, and derived totals.
 * Prices shown here are for display only — the server always
 * re-resolves prices from the database at checkout.
 */
export default function usePosCart() {
    const [lines, setLines] = useState([]);
    const [discount, setDiscount] = useState(0);
    const [discountType, setDiscountType] = useState('amount');

    function addLine(sellable, itemType) {
        setLines((current) => {
            const key = lineKey(itemType, sellable.id);
            const existing = current.find((line) => lineKey(line.item_type, line.item_id) === key);

            if (existing) {
                return current.map((line) =>
                    lineKey(line.item_type, line.item_id) === key ? { ...line, qty: line.qty + 1 } : line,
                );
            }

            return [
                ...current,
                {
                    item_type: itemType,
                    item_id: sellable.id,
                    name: sellable.name,
                    price: Number(sellable.price),
                    qty: 1,
                },
            ];
        });
    }

    function updateQty(itemType, itemId, qty) {
        const safeQty = Math.max(1, Math.floor(Number(qty)) || 1);
        const key = lineKey(itemType, itemId);

        setLines((current) =>
            current.map((line) => (lineKey(line.item_type, line.item_id) === key ? { ...line, qty: safeQty } : line)),
        );
    }

    function removeLine(itemType, itemId) {
        const key = lineKey(itemType, itemId);
        setLines((current) => current.filter((line) => lineKey(line.item_type, line.item_id) !== key));
    }

    function clear() {
        setLines([]);
        setDiscount(0);
        setDiscountType('amount');
    }

    const subtotal = useMemo(() => lines.reduce((sum, line) => sum + line.price * line.qty, 0), [lines]);

    const discountAmount = useMemo(() => {
        const value = Number(discount) || 0;

        if (discountType === 'percent') {
            return Math.min(subtotal * (value / 100), subtotal);
        }

        return Math.min(value, subtotal);
    }, [subtotal, discount, discountType]);

    const total = Math.max(subtotal - discountAmount, 0);

    return {
        lines,
        addLine,
        updateQty,
        removeLine,
        clear,
        discount,
        setDiscount,
        discountType,
        setDiscountType,
        subtotal,
        discountAmount,
        total,
    };
}
