<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Fine-grained permissions checked server-side via policies / Gate.
 * Grouped by module. Never rely on the frontend to enforce these.
 */
enum Permission: string
{
    // WABA configuration
    case WabaView = 'waba.view';
    case WabaManage = 'waba.manage';        // create/update credentials, run validators

    // Templates
    case TemplateView = 'template.view';
    case TemplateManage = 'template.manage'; // create/edit/delete
    case TemplateSubmit = 'template.submit'; // submit to Meta

    // Contacts
    case ContactView = 'contact.view';
    case ContactManage = 'contact.manage';
    case ContactImport = 'contact.import';
    case ContactExport = 'contact.export';

    // Campaigns
    case CampaignView = 'campaign.view';
    case CampaignManage = 'campaign.manage'; // create/edit
    case CampaignLaunch = 'campaign.launch'; // start/pause/resume/cancel

    // Messaging
    case MessageSend = 'message.send';       // individual / test sends
    case MessageView = 'message.view';

    // Reports
    case ReportView = 'report.view';
    case ReportExport = 'report.export';

    // Organization administration
    case OrgManage = 'org.manage';           // members, roles, settings
    case AuditView = 'audit.view';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $p) => $p->value, self::cases());
    }

    /**
     * Default permission set per organization role.
     *
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        $all = self::values();

        $viewer = [
            self::ReportView->value,
            self::MessageView->value,
            self::CampaignView->value,
            self::ContactView->value,
            self::TemplateView->value,
            self::WabaView->value,
        ];

        $support = [
            ...$viewer,
            self::MessageSend->value,
            self::ContactManage->value,
        ];

        $campaignManager = [
            ...$support,
            self::TemplateManage->value,
            self::TemplateSubmit->value,
            self::ContactImport->value,
            self::ContactExport->value,
            self::CampaignManage->value,
            self::CampaignLaunch->value,
            self::ReportExport->value,
        ];

        return [
            OrganizationRole::Viewer->value => $viewer,
            OrganizationRole::SupportAgent->value => $support,
            OrganizationRole::CampaignManager->value => $campaignManager,
            OrganizationRole::OrgAdmin->value => $all,
        ];
    }
}
