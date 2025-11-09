import { ReactNode, useState, FormEventHandler, useEffect } from 'react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { ChevronRightIcon, LoaderCircle } from 'lucide-react';
import { useForm, usePage } from '@inertiajs/react';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import type { SharedData } from '@/types';
import SourceControlSelect from '@/pages/source-controls/components/source-control-select';
import { Server } from '@/types/server';
import ServerSelect from '@/pages/servers/components/server-select';
import ServiceVersionSelect from '@/pages/services/components/service-version-select';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import DynamicField from '@/components/ui/dynamic-field';
import { TagsInput } from '@/components/ui/tags-input';
import DatabaseSelect from '@/pages/databases/components/database-select';
import DatabaseUserSelect from '@/pages/database-users/components/database-user-select';
import SelectRepo from '@/pages/source-controls/components/select-repo';
import SelectBranch from '@/pages/source-controls/components/select-branch';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Separator } from '@/components/ui/separator';
import * as LucideIcons from 'lucide-react';

type CreateSiteForm = {
  server: string;
  type: string;
  domain: string;
  aliases: string[];
  php_version: string;
  source_control: string;
  repository: string;
  branch: string;
  user: string;
};

export default function CreateSite({
  server,
  defaultOpen,
  onOpenChange,
  children,
}: {
  server?: Server;
  defaultOpen?: boolean;
  onOpenChange?: (open: boolean) => void;
  children: ReactNode;
}) {
  const page = usePage<SharedData>();
  const [open, setOpen] = useState(defaultOpen || false);

  useEffect(() => {
    if (defaultOpen !== undefined) {
      setOpen(defaultOpen);
    }
  }, [defaultOpen]);

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    if (onOpenChange) {
      onOpenChange(isOpen);
    }
  };

  const form = useForm<CreateSiteForm>({
    server: server?.id.toString() || '',
    type: 'php',
    domain: '',
    aliases: [],
    php_version: '',
    source_control: '',
    repository: '',
    branch: '',
    user: '',
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('sites.store', { server: form.data.server }));
  };

  useEffect(() => {
    const typeConfig = page.props.configs.site.types[form.data.type];

    if (typeConfig?.form) {
      typeConfig.form.forEach((field: DynamicFieldConfig) => {
        if (field.default !== undefined) {
          /* @ts-expect-error dynamic types */
          if (form.data[field.name] === '' || form.data[field.name] === undefined) {
            /* @ts-expect-error dynamic types */
            form.setData(field.name, field.default);
          }
        }
      });
    }
  }, [form.data.type]);

  const getColumnGridClass = (distribution?: string) => {
    const gridClasses: Record<string, string> = {
      '50/50': 'grid grid-cols-1 md:grid-cols-2 gap-4',
      '33/33/33': 'grid grid-cols-1 md:grid-cols-3 gap-4',
      '25/25/25/25': 'grid grid-cols-1 md:grid-cols-4 gap-4',
      '40/60': 'grid grid-cols-1 md:grid-cols-[2fr_3fr] gap-4',
      '60/40': 'grid grid-cols-1 md:grid-cols-[3fr_2fr] gap-4',
      '30/70': 'grid grid-cols-1 md:grid-cols-[3fr_7fr] gap-4',
      '70/30': 'grid grid-cols-1 md:grid-cols-[7fr_3fr] gap-4',
      '20/80': 'grid grid-cols-1 md:grid-cols-[1fr_4fr] gap-4',
      '80/20': 'grid grid-cols-1 md:grid-cols-[4fr_1fr] gap-4',
    };
    return distribution && gridClasses[distribution] ? gridClasses[distribution] : 'grid grid-cols-1 md:grid-cols-2 gap-4';
  };

  const getFormField = (field: DynamicFieldConfig) => {
    // Handle sections specially
    if (field.type === 'section') {
      const IconComponent = field.icon && LucideIcons[field.icon as keyof typeof LucideIcons];
      const layout = field.layout || 'collapsible';

      // Row layout - side-by-side columns on tablet+
      if (layout === 'row') {
        const gridClass = getColumnGridClass(field.columnDistribution);
        return (
          <div key={`row-${field.name}`} className="space-y-4">
            {(field.label || field.description) && (
              <div>
                {field.label && (
                  <div className="flex items-center gap-2 pt-4">
                    {IconComponent && <IconComponent className="h-5 w-5 text-muted-foreground" />}
                    <h3 className="text-lg font-semibold">{field.label}</h3>
                  </div>
                )}
                {field.description && <p className="text-sm text-muted-foreground">{field.description}</p>}
                <Separator className="mt-2" />
              </div>
            )}
            <div className={gridClass}>{field.children?.map((childConfig) => getFormField(childConfig))}</div>
          </div>
        );
      }

      // Standard section - non-collapsible
      if (layout === 'standard') {
        return (
          <div key={`section-${field.name}`} className="space-y-4">
            <div className="flex items-center gap-2 pt-4">
              {IconComponent && <IconComponent className="h-5 w-5 text-muted-foreground" />}
              <h3 className="text-lg font-semibold">{field.label || field.name}</h3>
            </div>
            {field.description && <p className="text-sm text-muted-foreground">{field.description}</p>}
            <Separator />
            <div className="space-y-4 pl-2">{field.children?.map((childConfig) => getFormField(childConfig))}</div>
          </div>
        );
      }

      // Collapsible section (default)
      return (
        <Collapsible
          key={`section-${field.name}`}
          defaultOpen={field.defaultOpen || false}
          className="group/collapsible rounded-lg border p-4 my-4"
        >
          <CollapsibleTrigger className="flex w-full items-center justify-between">
            <div className="flex items-center gap-2">
              {IconComponent && <IconComponent className="h-5 w-5 text-muted-foreground" />}
              <Label className="cursor-pointer text-base font-semibold">{field.label || field.name}</Label>
            </div>
            <ChevronRightIcon className="h-4 w-4 transition-transform group-data-[state=open]/collapsible:rotate-90" />
          </CollapsibleTrigger>
          {field.description && <p className="text-sm text-muted-foreground mt-2">{field.description}</p>}
          <CollapsibleContent className="mt-4 space-y-4">{field.children?.map((childConfig) => getFormField(childConfig))}</CollapsibleContent>
        </Collapsible>
      );
    }

    if (field.name === 'source_control') {
      return (
        <FormField key={`field-${field.name}`}>
          <Label htmlFor="source_control">Source Control</Label>
          <SourceControlSelect
            id="source_control"
            value={form.data.source_control}
            onValueChange={(value) => form.setData('source_control', value)}
          />
          <InputError message={form.errors.source_control} />
        </FormField>
      );
    }

    if (field.name === 'repository') {
      return (
        <FormField key={`field-${field.name}`}>
          <Label htmlFor="repository">Repository</Label>
          <SelectRepo
            sourceControlId={form.data.source_control}
            value={form.data.repository}
            onValueChange={(value) => form.setData('repository', value)}
            placeholder="owner/repository"
          />
          <InputError message={form.errors.repository} />
        </FormField>
      );
    }

    if (field.name === 'branch') {
      return (
        <FormField key={`field-${field.name}`}>
          <Label htmlFor="branch">Branch</Label>
          <SelectBranch
            sourceControlId={form.data.source_control}
            repository={form.data.repository}
            value={form.data.branch}
            onValueChange={(value) => form.setData('branch', value)}
            placeholder="e.g. main, master, develop"
          />
          <InputError message={form.errors.branch} />
        </FormField>
      );
    }

    if (field.name === 'php_version') {
      return (
        <FormField key={`field-${field.name}`}>
          <Label htmlFor="php_version">PHP Version</Label>
          <ServiceVersionSelect
            id="php_version"
            serverId={parseInt(form.data.server)}
            service="php"
            value={form.data.php_version}
            onValueChange={(value) => form.setData('php_version', value)}
          />
          <InputError message={form.errors.php_version} />
        </FormField>
      );
    }

    if (field.name === 'database') {
      return (
        <FormField key={`field-${field.name}`}>
          <Label htmlFor="database">Database</Label>
          <DatabaseSelect
            id="database"
            key={`field-${field.name}`}
            name="database"
            serverId={parseInt(form.data.server)}
            /*@ts-expect-error dynamic types*/
            value={form.data.database}
            /*@ts-expect-error dynamic types*/
            onValueChange={(value) => form.setData('database', value)}
            createWithUser={true}
          />
          {/*@ts-expect-error dynamic types*/}
          <InputError message={form.errors.database} />
        </FormField>
      );
    }

    if (field.name === 'database_user') {
      return (
        <FormField key={`field-${field.name}`}>
          <Label htmlFor="database-user">Database user</Label>
          <DatabaseUserSelect
            id="database-user"
            key={`field-${field.name}`}
            name="database_user"
            serverId={parseInt(form.data.server)}
            /*@ts-expect-error dynamic types*/
            value={form.data.database_user}
            /*@ts-expect-error dynamic types*/
            onValueChange={(value) => form.setData('database_user', value)}
            create={false}
          />
          {/*@ts-expect-error dynamic types*/}
          <InputError message={form.errors.database_user} />
        </FormField>
      );
    }

    return (
      <DynamicField
        key={`field-${field.name}`}
        /*@ts-expect-error dynamic types*/
        value={form.data[field.name]}
        /*@ts-expect-error dynamic types*/
        onChange={(value) => form.setData(field.name, value)}
        config={field}
        /*@ts-expect-error dynamic types*/
        error={form.errors[field.name]}
      />
    );
  };

  return (
    <Sheet open={open} onOpenChange={handleOpenChange}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="w-full lg:max-w-3xl">
        <SheetHeader>
          <SheetTitle>Create site</SheetTitle>
          <SheetDescription>Fill in the details to create a new site.</SheetDescription>
        </SheetHeader>
        <Form id="create-site-form" className="p-4" onSubmit={submit}>
          <FormFields>
            {server === undefined && (
              <FormField>
                <Label htmlFor="server">Server</Label>
                <ServerSelect value={form.data.server} onValueChange={(value) => form.setData('server', value ? value.id.toString() : '')} />
                <InputError message={form.errors.server} />
              </FormField>
            )}

            {form.data.server && (
              <>
                <FormField>
                  <Label htmlFor="type">Site Type</Label>
                  <Select value={form.data.type} onValueChange={(value) => form.setData('type', value)}>
                    <SelectTrigger id="type">
                      <SelectValue placeholder="Select site type" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        {Object.entries(page.props.configs.site.types).map(([key, type]) => (
                          <SelectItem key={`type-${key}`} value={key}>
                            {type.label}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  <InputError message={form.errors.type} />
                </FormField>

                <FormField>
                  <Label htmlFor="domain">Domain</Label>
                  <Input
                    id="domain"
                    type="text"
                    value={form.data.domain}
                    onChange={(e) => form.setData('domain', e.target.value)}
                    placeholder="vitodeploy.com"
                  />
                  <InputError message={form.errors.domain} />
                </FormField>

                <FormField>
                  <Label htmlFor="aliases">Aliases</Label>
                  <TagsInput
                    id="aliases"
                    type="text"
                    value={form.data.aliases}
                    placeholder="Add aliases"
                    onValueChange={(value) => form.setData('aliases', value)}
                  />
                  <InputError message={form.errors.aliases} />
                </FormField>

                {page.props.configs.site.types[form.data.type].form?.map((config) => getFormField(config))}

                <FormField>
                  <Label htmlFor="user">Isolated User (Optional)</Label>
                  <Input
                    id="user"
                    type="text"
                    value={form.data.user}
                    onChange={(e) => form.setData('user', e.target.value)}
                    placeholder="Leave empty for using server's default user"
                  />
                  <InputError message={form.errors.user} />
                </FormField>
              </>
            )}
          </FormFields>
        </Form>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button type="submit" form="create-site-form" disabled={form.processing || !form.data.server}>
              {form.processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />} Create
            </Button>
            <SheetClose asChild>
              <Button variant="outline" disabled={form.processing}>
                Cancel
              </Button>
            </SheetClose>
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
