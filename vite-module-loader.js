import fs from 'fs/promises';
import path from 'path';

async function collectModuleAssetsPaths(modulesPath) {
    return await getExportedModulesArrayAttributes(modulesPath, 'paths');
}

async function collectModulePlugins(modulesPath) {
    return await getExportedModulesArrayAttributes(modulesPath, 'plugins');
}

async function getExportedModulesArrayAttributes(modulesPath, attribute) {
    const result = [];
    modulesPath = path.join(__dirname, modulesPath);

    const moduleStatusesPath = path.join(__dirname, 'modules_statuses.json');

    let moduleStatuses = {};
    try {
        const moduleStatusesContent = await fs.readFile(moduleStatusesPath, 'utf-8');
        moduleStatuses = JSON.parse(moduleStatusesContent);
    } catch (error) {
        // If the statuses file is missing, assume no modules are enabled and return empty result silently
        if (error && error.code !== 'ENOENT') {
            // Log non-ENOENT errors as warnings
            console.warn(`Warning while reading module statuses: ${error}`);
        }
        return result;
    }

    try {
        // Read module directories
        const moduleDirectories = await fs.readdir(modulesPath);

        for (const moduleDir of moduleDirectories) {
            if (moduleDir === '.DS_Store') {
                // Skip .DS_Store directory
                continue;
            }

            // Check if the module is enabled (status is true)
            if (moduleStatuses[moduleDir] === true) {
                const viteConfigPath = path.join(modulesPath, moduleDir, 'vite.config.js');
                let stat;
                try {
                    stat = await fs.stat(viteConfigPath);
                } catch {
                    continue;
                }

                if (stat.isFile()) {
                    // Import the module-specific Vite configuration
                    const moduleConfig = await import(viteConfigPath);

                    if (moduleConfig[attribute] && Array.isArray(moduleConfig[attribute])) {
                        result.push(...moduleConfig[attribute]);
                    }
                }
            }
        }
    } catch {
        // If modules directory doesn't exist, return empty result silently
        return result;
    }

    return result;
}

export { collectModuleAssetsPaths, collectModulePlugins };
