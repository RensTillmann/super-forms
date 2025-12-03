import React from 'react';
import { motion, AnimatePresence } from 'framer-motion';

function Repeater({ label, items, onChange, defaultItem, renderItem }) {
  const addItem = () => {
    onChange([...items, { ...defaultItem, id: Date.now() }]);
  };

  const removeItem = (index) => {
    const newItems = items.filter((_, i) => i !== index);
    onChange(newItems);
  };

  const updateItem = (index, updatedItem) => {
    const newItems = [...items];
    newItems[index] = updatedItem;
    onChange(newItems);
  };

  return (
    <div className="space-y-3">
      {label && (
        <div className="flex items-center justify-between">
          <label className="text-sm font-medium text-gray-700">{label}</label>
          <button
            type="button"
            onClick={addItem}
            className="text-sm text-primary-600 hover:text-primary-700 font-medium"
          >
            Add Item
          </button>
        </div>
      )}
      
      <AnimatePresence>
        {items.map((item, index) => (
          <motion.div
            key={item.id || index}
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: 20 }}
            transition={{ duration: 0.2 }}
            className="relative border border-gray-200 rounded-lg p-4"
          >
            <button
              type="button"
              onClick={() => removeItem(index)}
              className="absolute top-2 right-2 p-1 text-gray-400 hover:text-red-500 transition-colors"
              title="Remove item"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
            
            {renderItem(item, index, updateItem)}
          </motion.div>
        ))}
      </AnimatePresence>
      
      {items.length === 0 && (
        <div className="text-center py-4 text-gray-500 border border-dashed border-gray-300 rounded-lg">
          <p className="text-sm">No items yet</p>
          <button
            type="button"
            onClick={addItem}
            className="mt-2 text-sm text-primary-600 hover:text-primary-700 font-medium"
          >
            Add your first item
          </button>
        </div>
      )}
    </div>
  );
}

export default Repeater;