<?php

declare(strict_types=1);

namespace Michael4d45\FilamentSystemInfo\Widgets;

use Carbon\Carbon;
use Composer\InstalledVersions;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class SystemInfoWidget extends BaseWidget
{
    protected string|null $heading = 'System Information';

    protected null|string $pollingInterval = '60s';

    /**
     * Configurable list of packages to monitor.
     * Each package should have: name, displayName, icon, and type ('packagist' or 'php')
     * @var array<array{name: string, displayName: string, icon: string, type: string}>
     */
    protected array $packages = [
        [
            'name' => 'laravel/framework',
            'displayName' => 'Laravel Version',
            'icon' => 'heroicon-o-cpu-chip',
            'type' => 'packagist',
        ],
        [
            'name' => 'php',
            'displayName' => 'PHP Version',
            'icon' => 'heroicon-o-code-bracket',
            'type' => 'php',
        ],
        [
            'name' => 'filament/filament',
            'displayName' => 'Filament Version',
            'icon' => 'heroicon-o-squares-2x2',
            'type' => 'packagist',
        ],
    ];

    /**
     * Whether to show deployment information
     */
    protected bool $showDeploymentInfo = true;

    /**
     * Whether to show security audit information
     */
    protected bool $showAuditInfo = true;

    /**
     * Path to release info file (relative to base_path)
     */
    protected string $releaseInfoPath = '.release-info';

    /**
     * Label for the security audit stat
     */
    protected string $securityAuditLabel = 'Composer Security Audit';

    /**
     * Configure the widget
     */
    public function configure(array $config = []): static
    {
        if (isset($config['packages'])) {
            $this->packages = $config['packages'];
        }

        if (isset($config['heading'])) {
            $this->heading = $config['heading'];
        }

        if (isset($config['pollingInterval'])) {
            $this->pollingInterval = $config['pollingInterval'];
        }

        if (isset($config['showDeploymentInfo'])) {
            $this->showDeploymentInfo = $config['showDeploymentInfo'];
        }

        if (isset($config['releaseInfoPath'])) {
            $this->releaseInfoPath = $config['releaseInfoPath'];
        }

        if (isset($config['showAuditInfo'])) {
            $this->showAuditInfo = $config['showAuditInfo'];
        }

        return $this;
    }

    /**
     * Set custom packages to monitor
     */
    public function packages(array $packages): static
    {
        $this->packages = $packages;
        return $this;
    }

    /**
     * Set the widget heading
     */
    public function heading(string $heading): static
    {
        $this->heading = $heading;
        return $this;
    }

    /**
     * Set polling interval
     */
    public function pollingInterval(string|null $interval): static
    {
        $this->pollingInterval = $interval;
        return $this;
    }

    /**
     * Enable/disable deployment info
     */
    public function showDeploymentInfo(bool $show = true): static
    {
        $this->showDeploymentInfo = $show;
        return $this;
    }

    /**
     * Set release info file path
     */
    public function releaseInfoPath(string $path): static
    {
        $this->releaseInfoPath = $path;
        return $this;
    }

    /**
     * Enable/disable audit info
     */
    public function showAuditInfo(bool $show = true): static
    {
        $this->showAuditInfo = $show;
        return $this;
    }

    protected function getStats(): array
    {
        $stats = [];

        // Get versions for configured packages
        foreach ($this->packages as $package) {
            $currentVersion = $this->getCurrentVersion($package);
            $latestVersion = $this->getLatestVersion($package);

            $stat = Stat::make($package['displayName'], $currentVersion)->icon(
                $package['icon'],
            );

            if (
                $latestVersion
                && $this->isOutdated($currentVersion, $latestVersion, $package)
            ) {
                $stat->color('danger')->description($latestVersion
                . ' available');
            }

            $stats[] = $stat;
        }

        // Add deployment information if enabled
        if ($this->showDeploymentInfo) {
            $deploymentStat = $this->getDeploymentStat();
            if ($deploymentStat) {
                $stats[] = $deploymentStat;
            }
        }

        // Add security audit information if enabled
        if ($this->showAuditInfo) {
            $auditStat = $this->getAuditStat();
            if ($auditStat) {
                $stats[] = $auditStat;
            }
        }

        return $stats;
    }

    private function getDeploymentStat(): null|Stat
    {
        $timeAgo = 'N/A';
        $commitMessage = 'N/A';
        $lastCommitTime = null;

        try {
            $result = Process::run(['git', 'log', '--format=%s|%ct', '-1']);
            $output = trim((string) $result->output());
            [$commitMessage, $timestampStr] = explode('|', $output, 2);
            $lastCommitTime = (int) $timestampStr;
            $timeAgo =
                Carbon::createFromTimestamp($lastCommitTime)->diffForHumans();
        } catch (\Exception $e) {
            // If git isn't available (e.g. .git excluded on production), try a fallback file
            try {
                $releaseInfoPath = base_path($this->releaseInfoPath);
                if (file_exists($releaseInfoPath)) {
                    $file = trim((string) file_get_contents($releaseInfoPath));
                    if ($file !== '') {
                        [$commitMessage, $timestampStr] = explode(
                            '|',
                            $file,
                            3,
                        );
                        $lastCommitTime = (int) $timestampStr;
                        $timeAgo =
                            Carbon::createFromTimestamp(
                                $lastCommitTime,
                            )->diffForHumans();
                    }
                }
            } catch (\Throwable $ignore) {
                // still ignore
            }
        }

        return Stat::make('Last Deployment', $timeAgo)
            ->description($commitMessage)
            ->icon('heroicon-o-clock')
            ->extraAttributes([
                'title' => $lastCommitTime
                    ? date('Y-m-d H:i:s T', $lastCommitTime)
                    : 'N/A',
            ]);
    }

    private function getAuditStat(): null|Stat
    {
        try {
            $composerHome = sys_get_temp_dir() . '/composer';
            if (!is_dir($composerHome)) {
                mkdir($composerHome, 0755, true);
            }
            $result = Process::command(['composer', 'audit', '--format=json'])
                ->path(base_path())
                ->env([
                    'COMPOSER_HOME' => $composerHome,
                    'HOME' => sys_get_temp_dir(),
                ])
                ->run();
            $output = trim($result->output());
            $error = trim($result->errorOutput());

            // Try to parse JSON output even if there are warnings
            $data = json_decode($output, true);
            if ($data !== null && isset($data['advisories'])) {
                $count = is_array($data['advisories']) ? count($data['advisories']) : 0;
                $value = $count > 0 ? "$count vulnerabilities" : 'Secure';
                $color = $count > 0 ? 'danger' : 'success';
                return Stat::make($this->securityAuditLabel, $value)
                    ->color($color)
                    ->icon('heroicon-o-shield-check');
            }

            // If not successful and no valid JSON, show error
            if (!$result->successful()) {
                if ($error === '') {
                    $error = 'Unknown error';
                }
                return Stat::make($this->securityAuditLabel, 'Check failed: ' . $error)
                    ->color('warning')
                    ->icon('heroicon-o-exclamation-triangle');
            }

            // If successful but no JSON (e.g., "No packages"), treat as secure
            if (str_contains($output, 'No packages') || str_contains($output, 'No security vulnerability advisories found')) {
                return Stat::make($this->securityAuditLabel, 'Secure')
                    ->color('success')
                    ->icon('heroicon-o-shield-check');
            }

            // Fallback for unexpected output
            return Stat::make($this->securityAuditLabel, 'Parse error')
                ->color('warning')
                ->icon('heroicon-o-exclamation-triangle');
        } catch (\Exception $e) {
            return Stat::make($this->securityAuditLabel, 'Error: ' . $e->getMessage())
                ->color('warning')
                ->icon('heroicon-o-exclamation-triangle');
        }
    }

    private function getLatestPhpVersion(): string|null
    {
        try {
            $response = Http::get('https://www.php.net/releases/active.php');
            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data)) {
                    $versions = [];
                    foreach ($data as $major => $minors) {
                        if (!is_array($minors)) {
                            continue;
                        }

                        foreach ($minors as $minor => $info) {
                            if (
                                !(
                                    is_array($info)
                                    && isset($info['version'])
                                    && is_string($info['version'])
                                )
                            ) {
                                continue;
                            }

                            $versions[] = $info['version'];
                        }
                    }

                    return $versions === [] ? null : max($versions);
                }
            }
        } catch (\Exception $e) {
        }

        return null;
    }

    private function getLatestVersionFromPackagist(string $package): string|null
    {
        try {
            $response = Http::get(
                "https://packagist.org/packages/{$package}.json",
            );
            if ($response->successful()) {
                $data = $response->json();
                if (
                    is_array($data)
                    && isset($data['package'])
                    && is_array($data['package'])
                    && isset($data['package']['versions'])
                    && is_array($data['package']['versions'])
                ) {
                    $versions = array_filter(
                        array_keys($data['package']['versions']),
                        fn($v) => (
                            is_string($v)
                            && !preg_match('/(dev|alpha|beta|rc)/i', $v)
                        ),
                    );
                    $versions = array_map(fn($v) => ltrim($v, 'v'), $versions);
                    usort($versions, fn(
                        string $a,
                        string $b,
                    ) => version_compare($a, $b));
                    $latest = end($versions);

                    return is_string($latest) ? $latest : null;
                }
            }
        } catch (\Exception $e) {
        }

        return null;
    }

    /**
     * @param array{name: string, displayName: string, icon: string, type: string} $package
     */
    private function getCurrentVersion(array $package): string
    {
        return match ($package['type']) {
            'packagist' => ltrim(
                InstalledVersions::getPrettyVersion($package['name'])
                ?? 'Unknown',
                'v',
            ),
            'php' => phpversion(),
            default => 'Unknown',
        };
    }

    /**
     * @param array{name: string, displayName: string, icon: string, type: string} $package
     */
    private function getLatestVersion(array $package): string|null
    {
        return match ($package['type']) {
            'packagist' => $this->getLatestVersionFromPackagist(
                $package['name'],
            ),
            'php' => $this->getLatestPhpVersion(),
            default => null,
        };
    }

    /**
     * @param array{name: string, displayName: string, icon: string, type: string} $package
     */
    private function isOutdated(
        string $currentVersion,
        string $latestVersion,
        array $package,
    ): bool {
        if ($currentVersion === 'Unknown') {
            return false;
        }

        return version_compare($currentVersion, $latestVersion, '<');
    }
}
