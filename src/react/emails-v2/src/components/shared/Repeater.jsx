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
    <div className="ev2-space-y-3">
      {label && (
        <div className="ev2-flex ev2-items-center ev2-justify-between">
          <label className="ev2-text-sm ev2-font-medium ev2-text-gray-700">{label}</label>
          <button
            type="button"
            onClick={addItem}
            className="ev2-text-sm ev2-text-primary-600 hover:ev2-text-primary-700 ev2-font-medium"
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
            className="ev2-relative ev2-border ev2-border-gray-200 ev2-rounded-lg ev2-p-4"
          >
            <button
              type="button"
              onClick={() => removeItem(index)}
              className="ev2-absolute ev2-top-2 ev2-right-2 ev2-p-1 ev2-text-gray-400 hover:ev2-text-red-500 ev2-transition-colors"
              title="Remove item"
            >
              <svg className="ev2-w-5 ev2-h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
            
            {renderItem(item, index, updateItem)}
          </motion.div>
        ))}
      </AnimatePresence>
      
      {items.length === 0 && (
        <div className="ev2-text-center ev2-py-4 ev2-text-gray-500 ev2-border ev2-border-dashed ev2-border-gray-300 ev2-rounded-lg">
          <p className="ev2-text-sm">No items yet</p>
          <button
            type="button"
            onClick={addItem}
            className="ev2-mt-2 ev2-text-sm ev2-text-primary-600 hover:ev2-text-primary-700 ev2-font-medium"
          >
            Add your first item
          </button>
        </div>
      )}
    </div>
  );
}

export default Repeater;