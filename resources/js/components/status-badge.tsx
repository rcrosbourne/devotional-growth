import { Badge } from '@/components/ui/badge';

export function StatusBadge({ status }: { status: 'draft' | 'published' }) {
    if (status === 'published') {
        return (
            <Badge className="border-moss/20 bg-moss/10 text-moss hover:bg-moss/10">
                Published
            </Badge>
        );
    }
    return (
        <Badge variant="secondary" className="text-on-surface-variant">
            Draft
        </Badge>
    );
}
