export interface DynamicFieldConfig {
  type: 'text' | 'textarea' | 'select' | 'checkbox' | 'component' | 'alert' | 'section';
  name: string;
  options?: string[] | { [key: string]: string };
  component?: string;
  placeholder?: string;
  description?: string;
  label?: string;
  default?: string | number | boolean;
  link?: {
    label: string;
    url: string;
  };
  className?: string;
  children?: DynamicFieldConfig[];
  layout?: 'collapsible' | 'standard' | 'row';
  defaultOpen?: boolean;
  icon?: string;
  sectionName?: string;
  order?: number;
  locked?: boolean;
  enforcedValue?: string | number | boolean;
  columnDistribution?: '50/50' | '33/33/33' | '25/25/25/25' | '40/60' | '60/40' | '30/70' | '70/30' | '20/80' | '80/20';
}
