import React from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import clsx from 'clsx';

function Accordion({ title, isOpen, onToggle, children, className }) {
  return (
    <div className={clsx('ev2-border-b ev2-border-gray-200', className)}>
      <button
        onClick={onToggle}
        className="ev2-w-full ev2-px-6 ev2-py-4 ev2-bg-gray-50 hover:ev2-bg-gray-100 ev2-flex ev2-items-center ev2-justify-between ev2-transition-colors ev2-text-left"
      >
        <span className="ev2-font-medium ev2-text-gray-900">{title}</span>
        <motion.svg
          animate={{ rotate: isOpen ? 180 : 0 }}
          transition={{ duration: 0.2 }}
          className="ev2-w-5 ev2-h-5 ev2-text-gray-500"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
        </motion.svg>
      </button>
      
      <AnimatePresence initial={false}>
        {isOpen && (
          <motion.div
            initial={{ height: 0 }}
            animate={{ height: 'auto' }}
            exit={{ height: 0 }}
            transition={{ duration: 0.3, ease: 'easeInOut' }}
            className="ev2-overflow-hidden"
          >
            <div className="ev2-px-6 ev2-py-4">
              {children}
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}

export default Accordion;