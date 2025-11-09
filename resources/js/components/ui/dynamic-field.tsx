import { InputHTMLAttributes, useEffect, useState } from 'react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import InputError from '@/components/ui/input-error';
import { FormField } from '@/components/ui/form';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { LockIcon, TriangleAlertIcon } from 'lucide-react';
import ServerProviderSelect from '@/pages/server-providers/components/server-provider-select';

interface DynamicFieldProps {
  value: string | number | boolean | string[] | undefined;
  onChange: (value: string | number | boolean | string[]) => void;
  config: DynamicFieldConfig;
  error?: string;
}

export default function DynamicField({ value, onChange, config, error }: DynamicFieldProps) {
  const defaultLabel = config.name.replaceAll('_', ' ');
  const label = config?.label || defaultLabel;
  const [initialValue, setInitialValue] = useState(false);

  if (!value) {
    value = config?.default || '';
  }

  useEffect(() => {
    if (!initialValue) {
      if (config.type === 'checkbox') {
        onChange((value as boolean) || false);
      } else {
        onChange(value);
      }
      setInitialValue(true);
    }
  }, [initialValue, setInitialValue, onChange, value, config]);

  // Handle alert
  if (config?.type === 'alert') {
    return (
      <FormField>
        <Alert>
          {!Array.isArray(config.options) && config.options?.type === 'warning' && <TriangleAlertIcon className="text-warning!" />}
          {config.label && <AlertTitle>{config.label}</AlertTitle>}
          <AlertDescription>
            {config.description}
            {config.link && (
              <a href={config.link.url} target="_blank" className="text-primary underline">
                {config.link.label}
              </a>
            )}
          </AlertDescription>
        </Alert>
      </FormField>
    );
  }

  // Handle section
  if (config?.type === 'section') {
    const IconComponent = config.icon && LucideIcons[config.icon as keyof typeof LucideIcons];

    // Non-collapsible section - just a visual separator
    if (config.canCollapse === false) {
      return (
        <div key={`section-${config.name}`} className="space-y-4">
          <div className="flex items-center gap-2 pt-4">
            {IconComponent && <IconComponent className="h-5 w-5 text-muted-foreground" />}
            <h3 className="text-lg font-semibold">{label}</h3>
          </div>
          {config.description && <p className="text-sm text-muted-foreground">{config.description}</p>}
          <Separator />
          <div className="space-y-4 pl-2">
            {config.children?.map((childConfig) => (
              <DynamicField
                key={`field-${childConfig.name}`}
                value={value?.[childConfig.name as keyof typeof value]}
                onChange={(childValue) => onChange({ ...(value as object), [childConfig.name]: childValue })}
                config={childConfig}
                error={error?.[childConfig.name as keyof typeof error]}
              />
            ))}
          </div>
        </div>
      );
    }

    // Collapsible section - accordion style
    return (
      <Collapsible key={`section-${config.name}`} defaultOpen={config.defaultOpen || false} className="group/collapsible rounded-lg border p-4 my-4">
        <CollapsibleTrigger className="flex w-full items-center justify-between">
          <div className="flex items-center gap-2">
            {IconComponent && <IconComponent className="h-5 w-5 text-muted-foreground" />}
            <Label className="cursor-pointer text-base font-semibold">{label}</Label>
          </div>
          <ChevronRightIcon className="h-4 w-4 transition-transform group-data-[state=open]/collapsible:rotate-90" />
        </CollapsibleTrigger>
        {config.description && <p className="text-sm text-muted-foreground mt-2">{config.description}</p>}
        <CollapsibleContent className="mt-4 space-y-4">
          {config.children?.map((childConfig) => (
            <DynamicField
              key={`field-${childConfig.name}`}
              value={value?.[childConfig.name as keyof typeof value]}
              onChange={(childValue) => onChange({ ...(value as object), [childConfig.name]: childValue })}
              config={childConfig}
              error={error?.[childConfig.name as keyof typeof error]}
            />
          ))}
        </CollapsibleContent>
      </Collapsible>
    );
  }

  // Handle checkbox
  if (config?.type === 'checkbox') {
    const isLocked = config?.locked || false;
    const displayValue = isLocked && config?.enforcedValue !== undefined ? config.enforcedValue : value;

    return (
      <FormField>
        <div className="flex items-center space-x-2">
          <Switch
            id={`switch-${config.name}`}
            defaultChecked={displayValue as boolean}
            onCheckedChange={onChange}
            disabled={isLocked}
            className={isLocked ? 'opacity-60 cursor-not-allowed' : ''}
          />
          <Label htmlFor={`switch-${config.name}`} className="flex items-center gap-2">
            {label}
            {isLocked && <LockIcon className="h-3 w-3 text-muted-foreground" />}
          </Label>
          {config.description && <p className="text-muted-foreground text-xs">{config.description}</p>}
          <InputError message={error} />
        </div>
      </FormField>
    );
  }

  // Handle select
  if (config?.type === 'select' && config.options) {
    const isLocked = config?.locked || false;
    const displayValue = isLocked && config?.enforcedValue !== undefined ? config.enforcedValue : value;

    return (
      <FormField>
        <Label htmlFor={`field-${config.name}`} className="capitalize flex items-center gap-2">
          {label}
          {isLocked && <LockIcon className="h-3 w-3 text-muted-foreground" />}
        </Label>
        <Select defaultValue={displayValue as string} onValueChange={onChange} disabled={isLocked}>
          <SelectTrigger id={`field-${config.name}`} className={isLocked ? 'opacity-60 cursor-not-allowed' : ''}>
            <SelectValue placeholder={config.placeholder || `Select ${label}`} />
          </SelectTrigger>
          <SelectContent>
            <SelectGroup>
              {Array.isArray(config.options) &&
                config.options.map((item) => (
                  <SelectItem key={`${config.name}-${item}`} value={item}>
                    {item}
                  </SelectItem>
                ))}
            </SelectGroup>
          </SelectContent>
        </Select>
        {config.description && <p className="text-muted-foreground text-xs">{config.description}</p>}
        <InputError message={error} />
      </FormField>
    );
  }

  // Handle textarea
  if (config?.type === 'textarea') {
    const isLocked = config?.locked || false;
    const displayValue = isLocked && config?.enforcedValue !== undefined ? config.enforcedValue : value;

    return (
      <FormField>
        <Label htmlFor={`field-${config.name}`} className="capitalize flex items-center gap-2">
          {label}
          {isLocked && <LockIcon className="h-3 w-3 text-muted-foreground" />}
        </Label>
        <Textarea
          name={config.name}
          id={`field-${config.name}`}
          defaultValue={(displayValue as string) || ''}
          placeholder={config.placeholder}
          onChange={(e) => onChange(e.target.value)}
          className={config.className}
          disabled={isLocked}
        />
        {config.description && <p className="text-muted-foreground text-xs">{config.description}</p>}
        <InputError message={error} />
      </FormField>
    );
  }

  // Handle server provider select
  if (config?.type === 'component' && config?.name === 'server_provider') {
    return (
      <FormField>
        <Label htmlFor={`field-${config.name}`} className="capitalize">
          {label}
        </Label>
        <ServerProviderSelect value={value as string} onValueChange={(value) => onChange(value)} />
        {config.description && <p className="text-muted-foreground text-xs">{config.description}</p>}
        <InputError message={error} />
      </FormField>
    );
  }

  // Default to text input
  const props: InputHTMLAttributes<HTMLInputElement> = {};
  if (config?.placeholder) {
    props.placeholder = config.placeholder;
  }

  // Handle locked with enforcedValue
  const isLocked = config?.locked || false;
  const displayValue = isLocked && config?.enforcedValue !== undefined ? config.enforcedValue : value;

  return (
    <FormField>
      <Label htmlFor={`field-${config.name}`} className="capitalize flex items-center gap-2">
        {label}
        {isLocked && <LockIcon className="h-3 w-3 text-muted-foreground" />}
      </Label>
      <Input
        type="text"
        name={config.name}
        id={`field-${config.name}`}
        defaultValue={(displayValue as string) || ''}
        onChange={(e) => onChange(e.target.value)}
        disabled={isLocked}
        className={isLocked ? 'opacity-60 cursor-not-allowed' : ''}
        {...props}
      />
      {config.description && <p className="text-muted-foreground text-xs">{config.description}</p>}
      <InputError message={error} />
    </FormField>
  );
}
