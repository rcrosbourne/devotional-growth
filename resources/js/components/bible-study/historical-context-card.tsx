interface HistoricalContext {
    setting: string;
    author: string;
    date_range: string;
    audience: string;
    historical_events: string;
}

export function HistoricalContextCard({
    context,
}: {
    context: HistoricalContext;
}) {
    return (
        <section className="rounded-2xl border border-border bg-surface-container-low p-5">
            <h2 className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                Historical Context
            </h2>
            <dl className="mt-4 space-y-3 text-sm">
                <Field label="Setting" value={context.setting} />
                <Field label="Author" value={context.author} />
                <Field label="Date" value={context.date_range} />
                <Field label="Audience" value={context.audience} />
                <Field label="Events" value={context.historical_events} />
            </dl>
        </section>
    );
}

function Field({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-xs font-bold text-on-surface-variant/70 uppercase">
                {label}
            </dt>
            <dd className="mt-0.5 text-on-surface">{value}</dd>
        </div>
    );
}
