import { categoryAnchor } from '@/lib/salonMenu';
import { formatMenuPrice } from '@/lib/money';
import { usePage } from '@inertiajs/react';

export default function PackageMenu({ menu = [] }) {
    const { salonContact } = usePage().props;
    const currency = salonContact?.currency || 'Rs';

    if (menu.length === 0) {
        return null;
    }

    return (
        <div className="mx-auto max-w-6xl px-6">
            <div className="mx-auto max-w-2xl text-center">
                <h2 className="font-display text-3xl font-semibold text-charcoal-900 sm:text-4xl">
                    Package Menu
                </h2>
                <p className="mt-4 text-charcoal-500">
                    Opening-season flyer prices. Ask in salon for length-based
                    colour and custom plans.
                </p>
            </div>

            <div className="mt-14 grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
                {menu.map((category) => {
                    const anchor = categoryAnchor(category.title);

                    return (
                        <article
                            key={category.title}
                            id={anchor}
                            className="scroll-mt-28"
                            aria-labelledby={`menu-${anchor}`}
                        >
                            <div className="flex items-end justify-between gap-3 border-b border-charcoal-100 pb-3">
                                <h3
                                    id={`menu-${anchor}`}
                                    className="font-display text-2xl font-semibold text-charcoal-900"
                                >
                                    {category.title}
                                </h3>
                                {category.badge ? (
                                    <span className="shrink-0 text-[11px] font-semibold uppercase tracking-[0.16em] text-rose-600">
                                        {category.badge}
                                    </span>
                                ) : null}
                            </div>

                            <ul className="mt-5 space-y-3.5">
                                {category.items?.map((item) => (
                                    <li
                                        key={`${category.title}-${item.name}`}
                                        className="flex items-baseline justify-between gap-4 text-sm"
                                    >
                                        <span className="text-charcoal-700">
                                            {item.name}
                                        </span>
                                        <span className="shrink-0 font-semibold text-rose-600">
                                            {formatMenuPrice(
                                                item.price,
                                                currency,
                                            )}
                                        </span>
                                    </li>
                                ))}
                            </ul>

                            {category.note ? (
                                <p className="mt-4 text-xs text-charcoal-300">
                                    {category.note}
                                </p>
                            ) : null}
                        </article>
                    );
                })}
            </div>
        </div>
    );
}
