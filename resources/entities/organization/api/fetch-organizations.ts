import { client } from '@/shared/api';
import type { Organization, OrganizationsResponse } from '../model/organization';

export async function fetchOrganizations(page: number = 1): Promise<OrganizationsResponse> {
    return client<OrganizationsResponse>('/api/organizations', {
        query: { page },
    });
}

export async function createOrganization(url: string): Promise<Organization> {
    const data = await client<{ data: Organization }>('/api/organizations', {
        method: 'POST',
        body: { url },
    });

    return data.data;
}

export async function fetchOrganization(id: number): Promise<Organization> {
    const data = await client<{ data: Organization }>(`/api/organizations/${id}`);

    return data.data;
}

export async function refreshOrganization(id: number): Promise<Organization> {
    const data = await client<{ data: Organization }>(`/api/organizations/${id}/refresh`, { method: 'POST' });

    return data.data;
}

export async function deleteOrganization(id: number): Promise<void> {
    await client(`/api/organizations/${id}`, { method: 'DELETE' });
}
