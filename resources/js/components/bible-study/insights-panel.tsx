interface CrossRef {
    book: string;
    chapter: number;
    verse_start: number;
    verse_end?: number | null;
    note?: string;
}

interface Insight {
    interpretation: string;
    application: string;
    cross_references: CrossRef[];
    literary_context: string;
}

export function InsightsPanel({ insight }: { insight: Insight }) {
    return (
        <section className="rounded-2xl border border-border bg-surface-container-low p-5">
            <h2 className="text-xs font-medium tracking-[0.15em] text-on-surface-variant uppercase">
                Insights
            </h2>

            <div className="mt-4 space-y-4 text-sm leading-relaxed text-on-surface/90">
                <Block title="Interpretation" body={insight.interpretation} />
                <Block title="Application" body={insight.application} />
                <Block
                    title="Literary Context"
                    body={insight.literary_context}
                />

                {insight.cross_references.length > 0 && (
                    <div>
                        <h3 className="mb-1 text-xs font-bold text-on-surface-variant uppercase">
                            Cross-references
                        </h3>
                        <ul className="space-y-1">
                            {insight.cross_references.map((ref) => (
                                <li
                                    key={`${ref.book}-${ref.chapter}-${ref.verse_start}`}
                                    className="text-on-surface"
                                >
                                    <span className="font-medium">
                                        {ref.book} {ref.chapter}:
                                        {ref.verse_start}
                                        {ref.verse_end
                                            ? `–${ref.verse_end}`
                                            : ''}
                                    </span>
                                    {ref.note && (
                                        <span className="ml-2 text-on-surface-variant">
                                            — {ref.note}
                                        </span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        </section>
    );
}

function Block({ title, body }: { title: string; body: string }) {
    return (
        <div>
            <h3 className="mb-1 text-xs font-bold text-on-surface-variant uppercase">
                {title}
            </h3>
            <p className="text-on-surface">{body}</p>
        </div>
    );
}
