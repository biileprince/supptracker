import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { BarChart3, Download, FileText, TrendingUp, CheckCircle, Clock, AlertCircle } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useState } from 'react';
import {
    BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
    PieChart, Pie, Cell, Legend
} from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reports', href: '/reports' },
];

interface StatusStat {
    name: string;
    value: number;
    color: string;
}

interface ChartDataPoint {
    date: string;
    done: number;
    inProgress: number;
    pending: number;
}

interface RecentUpdate {
    id: number;
    status: string;
    remark: string | null;
    activity_date: string;
    created_at: string;
    user?: { id: number; name: string };
    activity?: { id: number; title: string };
}

interface ReportProps {
    summary: {
        totalActivities: number;
        expectedUpdates: number;
        completionRate: number;
        statusStats: StatusStat[];
    };
    chartData: ChartDataPoint[];
    recentUpdates: RecentUpdate[];
    dateRange: {
        start: string;
        end: string;
    };
}

export default function ReportsIndex({ summary, chartData, recentUpdates, dateRange }: ReportProps) {
    const [startDate, setStartDate] = useState(dateRange.start);
    const [endDate, setEndDate] = useState(dateRange.end);

    const applyFilters = () => {
        router.get(route('reports.index'), {
            start_date: startDate,
            end_date: endDate,
        }, { preserveState: true });
    };

    const formatStatus = (status: string) => {
        switch (status) {
            case 'done': return 'Done';
            case 'in_progress': return 'In Progress';
            default: return 'Pending';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reports" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 max-w-6xl mx-auto w-full">
                {/* Header */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight">Reports</h2>
                        <p className="text-muted-foreground">
                            Analyze activity performance and export reports.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <a href={route('reports.export.csv', { start_date: startDate, end_date: endDate })}>
                                <Download className="mr-2 h-4 w-4" />
                                CSV
                            </a>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <a href={route('reports.export.pdf', { start_date: startDate, end_date: endDate })}>
                                <FileText className="mr-2 h-4 w-4" />
                                PDF
                            </a>
                        </Button>
                    </div>
                </div>

                {/* Date Range Filter */}
                <Card className="rounded-xl shadow-sm">
                    <CardContent className="p-4">
                        <div className="flex flex-col sm:flex-row items-start sm:items-end gap-4">
                            <div className="grid gap-1.5">
                                <label htmlFor="start_date" className="text-sm font-medium">From</label>
                                <input
                                    id="start_date"
                                    type="date"
                                    value={startDate}
                                    onChange={(e) => setStartDate(e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <label htmlFor="end_date" className="text-sm font-medium">To</label>
                                <input
                                    id="end_date"
                                    type="date"
                                    value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                />
                            </div>
                            <Button size="sm" className="h-9" onClick={applyFilters}>
                                Apply
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card className="rounded-xl shadow-sm">
                        <CardContent className="p-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground font-medium">Total Activities</p>
                                    <p className="text-3xl font-bold mt-1">{summary.totalActivities}</p>
                                </div>
                                <div className="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-950/50 flex items-center justify-center">
                                    <BarChart3 className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="rounded-xl shadow-sm">
                        <CardContent className="p-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground font-medium">Completion Rate</p>
                                    <p className="text-3xl font-bold mt-1">{summary.completionRate}%</p>
                                </div>
                                <div className="h-10 w-10 rounded-full bg-green-100 dark:bg-green-950/50 flex items-center justify-center">
                                    <TrendingUp className="h-5 w-5 text-green-600 dark:text-green-400" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="rounded-xl shadow-sm">
                        <CardContent className="p-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground font-medium">Expected Updates</p>
                                    <p className="text-3xl font-bold mt-1">{summary.expectedUpdates}</p>
                                </div>
                                <div className="h-10 w-10 rounded-full bg-purple-100 dark:bg-purple-950/50 flex items-center justify-center">
                                    <CheckCircle className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Charts Row */}
                <div className="grid gap-4 md:grid-cols-5">
                    {/* Bar Chart */}
                    <Card className="rounded-xl shadow-sm md:col-span-3">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base font-semibold">Daily Completion</CardTitle>
                        </CardHeader>
                        <CardContent className="p-4">
                            {chartData.length > 0 ? (
                                <ResponsiveContainer width="100%" height={280}>
                                    <BarChart data={chartData} barCategoryGap="20%">
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                                        <XAxis dataKey="date" className="text-xs" tick={{ fontSize: 11 }} />
                                        <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                        <Tooltip
                                            contentStyle={{
                                                backgroundColor: 'hsl(var(--card))',
                                                border: '1px solid hsl(var(--border))',
                                                borderRadius: '0.5rem',
                                                fontSize: '12px',
                                            }}
                                        />
                                        <Bar dataKey="done" fill="#22c55e" name="Done" radius={[4, 4, 0, 0]} />
                                        <Bar dataKey="inProgress" fill="#eab308" name="In Progress" radius={[4, 4, 0, 0]} />
                                        <Bar dataKey="pending" fill="#94a3b8" name="Pending" radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="flex items-center justify-center h-[280px] text-muted-foreground">
                                    No data for the selected period.
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Pie Chart */}
                    <Card className="rounded-xl shadow-sm md:col-span-2">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base font-semibold">Status Distribution</CardTitle>
                        </CardHeader>
                        <CardContent className="p-4">
                            {summary.statusStats.some(s => s.value > 0) ? (
                                <ResponsiveContainer width="100%" height={280}>
                                    <PieChart>
                                        <Pie
                                            data={summary.statusStats}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={55}
                                            outerRadius={90}
                                            paddingAngle={3}
                                            dataKey="value"
                                        >
                                            {summary.statusStats.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={entry.color} />
                                            ))}
                                        </Pie>
                                        <Tooltip
                                            contentStyle={{
                                                backgroundColor: 'hsl(var(--card))',
                                                border: '1px solid hsl(var(--border))',
                                                borderRadius: '0.5rem',
                                                fontSize: '12px',
                                            }}
                                        />
                                        <Legend
                                            verticalAlign="bottom"
                                            iconSize={10}
                                            wrapperStyle={{ fontSize: '12px' }}
                                        />
                                    </PieChart>
                                </ResponsiveContainer>
                            ) : (
                                <div className="flex items-center justify-center h-[280px] text-muted-foreground">
                                    No data available.
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Updates Table */}
                <Card className="rounded-xl shadow-sm">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base font-semibold">Recent Updates</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {Array.isArray(recentUpdates) && recentUpdates.length > 0 ? (
                            <div className="w-full overflow-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="border-b bg-muted/50">
                                        <tr>
                                            <th className="p-3 font-medium">Activity</th>
                                            <th className="p-3 font-medium hidden sm:table-cell">Date</th>
                                            <th className="p-3 font-medium">Personnel</th>
                                            <th className="p-3 font-medium">Status</th>
                                            <th className="p-3 font-medium hidden md:table-cell">Remark</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {recentUpdates.map((update) => (
                                            <tr key={update.id} className="border-b last:border-0 hover:bg-muted/50 transition-colors">
                                                <td className="p-3 font-medium">{update.activity?.title ?? 'N/A'}</td>
                                                <td className="p-3 hidden sm:table-cell text-muted-foreground">
                                                    {new Date(update.activity_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                                                </td>
                                                <td className="p-3">{update.user?.name ?? 'N/A'}</td>
                                                <td className="p-3">
                                                    <span className={`inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full ${
                                                        update.status === 'done'
                                                            ? 'bg-green-100 text-green-700 dark:bg-green-950/50 dark:text-green-400'
                                                            : update.status === 'in_progress'
                                                                ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950/50 dark:text-yellow-400'
                                                                : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                                                    }`}>
                                                        {update.status === 'done' && <CheckCircle className="w-3 h-3" />}
                                                        {update.status === 'in_progress' && <Clock className="w-3 h-3" />}
                                                        {update.status === 'pending' && <AlertCircle className="w-3 h-3" />}
                                                        {formatStatus(update.status)}
                                                    </span>
                                                </td>
                                                <td className="p-3 hidden md:table-cell text-muted-foreground max-w-[200px] truncate">
                                                    {update.remark || '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="flex flex-col items-center justify-center p-8 text-center">
                                <p className="text-muted-foreground">No updates found for this date range.</p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
