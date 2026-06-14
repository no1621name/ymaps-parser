import { OrganizationStatus } from './organization-status';

export interface Organization {
    id: number;
    business_id: string;
    name: string;
    avg_rating: number | null;
    reviews_count: number | null;
    ratings_count: number | null;
    status: OrganizationStatus;
    error_message: string | null;
    parsed_at: string | null;
}
