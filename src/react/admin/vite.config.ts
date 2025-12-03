import { defineConfig, Plugin } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import { visualizer } from 'rollup-plugin-visualizer';
import { resolve } from 'path';
import { renameSync, mkdirSync, existsSync } from 'fs';

// Plugin to strip data-testid attributes in production builds
function removeTestIdPlugin(): Plugin {
  return {
    name: 'remove-data-testid',
    enforce: 'pre',
    transform(code: string, id: string) {
      // Only process JSX/TSX files
      if (!id.match(/\.[jt]sx$/)) return null;
      // Remove data-testid="..." and data-testid='...' attributes
      const transformed = code
        .replace(/\s+data-testid=["'][^"']*["']/g, '')
        .replace(/\s+data-testid=\{[^}]*\}/g, '');
      if (transformed !== code) {
        return { code: transformed, map: null };
      }
      return null;
    },
  };
}

// Plugin to move CSS to correct location after build
function moveCssPlugin(): Plugin {
  return {
    name: 'move-css',
    closeBundle() {
      const srcCss = resolve(__dirname, '../../assets/js/backend/admin.css');
      const destDir = resolve(__dirname, '../../assets/css/backend');
      const destCss = resolve(destDir, 'admin.css');

      if (existsSync(srcCss)) {
        if (!existsSync(destDir)) {
          mkdirSync(destDir, { recursive: true });
        }
        renameSync(srcCss, destCss);
        console.log('✓ Moved admin.css to assets/css/backend/');

        // Also move source map if exists
        const srcMap = srcCss + '.map';
        if (existsSync(srcMap)) {
          renameSync(srcMap, destCss + '.map');
          console.log('✓ Moved admin.css.map to assets/css/backend/');
        }
      }
    }
  };
}

export default defineConfig(({ mode }) => ({
  plugins: [
    tailwindcss(),
    react({
      // Use automatic JSX runtime - we bundle React so internals are available
      jsxRuntime: 'automatic',
    }),
    // Strip data-testid attributes only in production builds when STRIP_TESTID=1
    // For dev server testing, we keep them to aid debugging
    mode === 'production' && process.env.STRIP_TESTID === '1' && removeTestIdPlugin(),
    moveCssPlugin(),
    // Bundle visualizer - generates stats.html when ANALYZE=1
    process.env.ANALYZE === '1' && visualizer({
      filename: 'stats.html',
      open: false,
      gzipSize: true,
      brotliSize: true,
    }),
  ].filter(Boolean),
  resolve: {
    alias: {
      '@': resolve(__dirname, '.'),
      '@shared': resolve(__dirname, 'components/shared'),
    },
  },
  build: {
    outDir: '../../assets/js/backend',
    emptyOutDir: false,
    rollupOptions: {
      input: resolve(__dirname, 'index.tsx'),
      // Bundle React - required for Radix UI / shadcn components that need React internals
      // This is safe because our admin UI runs on isolated pages (super_create_form)
      output: {
        format: 'iife',
        name: 'SuperFormsAdmin',
        entryFileNames: 'admin.js',
        assetFileNames: 'admin[extname]',
        // IIFE doesn't support code splitting, so inline dynamic imports
        inlineDynamicImports: true,
      },
    },
    sourcemap: true,
    // Use esbuild for fast minification in production
    minify: 'esbuild',
    cssCodeSplit: false,
    // Target modern browsers for smaller bundle
    target: 'es2020',
  },
}));
