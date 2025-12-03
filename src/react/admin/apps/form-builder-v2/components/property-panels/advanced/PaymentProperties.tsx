import React from 'react';
import { PropertyField } from '../shared';

interface PaymentPropertiesProps {
  element: any;
  onUpdate: (property: string, value: any) => void;
}

export const PaymentProperties: React.FC<PaymentPropertiesProps> = ({ 
  element, 
  onUpdate 
}) => {
  return (
    <>
      <PropertyField label="Amount Type">
        <select
          value={element.properties?.amountType || 'fixed'}
          onChange={(e) => onUpdate('amountType', e.target.value)}
          className="form-input"
        >
          <option value="fixed">Fixed Amount</option>
          <option value="user">User Defined</option>
          <option value="calculated">Calculated</option>
        </select>
      </PropertyField>

      {element.properties?.amountType === 'fixed' && (
        <PropertyField label="Amount">
          <input
            type="number"
            value={element.properties?.amount || ''}
            onChange={(e) => onUpdate('amount', parseFloat(e.target.value) || 0)}
            className="form-input"
            min="0"
            step="0.01"
            placeholder="0.00"
          />
        </PropertyField>
      )}

      {element.properties?.amountType === 'calculated' && (
        <PropertyField label="Calculation Formula">
          <input
            type="text"
            value={element.properties?.formula || ''}
            onChange={(e) => onUpdate('formula', e.target.value)}
            className="form-input"
            placeholder="field1 * field2 + 10"
          />
        </PropertyField>
      )}

      <PropertyField label="Currency">
        <select
          value={element.properties?.currency || 'USD'}
          onChange={(e) => onUpdate('currency', e.target.value)}
          className="form-input"
        >
          <option value="USD">USD ($)</option>
          <option value="EUR">EUR (€)</option>
          <option value="GBP">GBP (£)</option>
          <option value="CAD">CAD ($)</option>
          <option value="AUD">AUD ($)</option>
          <option value="JPY">JPY (¥)</option>
        </select>
      </PropertyField>

      <PropertyField label="Payment Methods">
        <div className="space-y-2">
          {['card', 'paypal', 'apple_pay', 'google_pay', 'bank_transfer'].map((method) => (
            <label key={method} className="flex items-center">
              <input
                type="checkbox"
                checked={element.properties?.paymentMethods?.includes(method) || false}
                onChange={(e) => {
                  const methods = element.properties?.paymentMethods || [];
                  const newMethods = e.target.checked
                    ? [...methods, method]
                    : methods.filter((m: string) => m !== method);
                  onUpdate('paymentMethods', newMethods);
                }}
                className="form-checkbox mr-2"
              />
              <span className="capitalize">{method.replace('_', ' ')}</span>
            </label>
          ))}
        </div>
      </PropertyField>

      <PropertyField label="Collect Billing Address">
        <input
          type="checkbox"
          checked={element.properties?.collectBillingAddress || false}
          onChange={(e) => onUpdate('collectBillingAddress', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>

      <PropertyField label="Collect Shipping Address">
        <input
          type="checkbox"
          checked={element.properties?.collectShippingAddress || false}
          onChange={(e) => onUpdate('collectShippingAddress', e.target.checked)}
          className="form-checkbox"
        />
      </PropertyField>

      <PropertyField label="Tax Rate (%)">
        <input
          type="number"
          value={element.properties?.taxRate || ''}
          onChange={(e) => onUpdate('taxRate', parseFloat(e.target.value) || 0)}
          className="form-input"
          min="0"
          max="100"
          step="0.01"
          placeholder="0.00"
        />
      </PropertyField>

      <PropertyField label="Success Message">
        <textarea
          value={element.properties?.successMessage || ''}
          onChange={(e) => onUpdate('successMessage', e.target.value)}
          className="form-input"
          rows={3}
          placeholder="Thank you for your payment!"
        />
      </PropertyField>
    </>
  );
};