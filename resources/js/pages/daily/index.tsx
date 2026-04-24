import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type Activity, type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Calendar, ChevronLeft, ChevronRight, CheckCircle, Clock, AlertCircle } from 'lucide-react';
import { ActivityUpdateForm } from '@/components/activity-update-form';

export default function DailyView({ activities, currentDate }: { activities: Activity[], currentDate: string }) {
    const { auth } = usePage<SharedData>().props;
    const currentUserId = auth.user.id;

    // Helper functions for date navigation
    const getNextDay = () => {
        const date = new Date(currentDate);
        date.setDate(date.getDate() + 1);
        return date.toISOString().split('T')[0];
    };

    const getPrevDay = () => {
        const date = new Date(currentDate);
        date.setDate(date.getDate() - 1);
        return date.toISOString().split('T')[0];
    };

    const todayString = new Date().toISOString().split('T')[0];
    const isToday = currentDate === todayString;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
        {
            title: 'Daily Handover',
            href: '/daily',
        },
    ];

    // Format date for display
    const formattedDate = new Date(currentDate).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Daily Handover - ${formattedDate}`} />
            
            <div className="flex h-full flex-1 flex-col gap-6 p-4 max-w-5xl mx-auto w-full">
                {/* Header and Date Navigation */}
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-card p-4 rounded-xl shadow-sm border">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight">Daily Handover</h2>
                        <p className="text-muted-foreground flex items-center mt-1">
                            <Calendar className="w-4 h-4 mr-2" />
                            {formattedDate} {isToday && <span className="ml-2 px-2 py-0.5 bg-primary/10 text-primary text-xs rounded-full font-medium">Today</span>}
                        </p>
                    </div>
                    
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="icon" asChild>
                            <Link href={route('daily.index', { date: getPrevDay() })}>
                                <ChevronLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <Button variant="outline" asChild className={isToday ? "bg-muted" : ""}>
                            <Link href={route('daily.index')}>
                                Today
                            </Link>
                        </Button>
                        <Button variant="outline" size="icon" asChild disabled={isToday}>
                            <Link href={route('daily.index', { date: getNextDay() })}>
                                <ChevronRight className="h-4 w-4" />
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Activities List */}
                <div className="flex flex-col gap-4">
                    {activities.length === 0 ? (
                        <div className="text-center p-12 bg-card rounded-xl border border-dashed">
                            <h3 className="text-lg font-medium">No activities found</h3>
                            <p className="text-muted-foreground mt-2">There are no daily activities configure to be tracked.</p>
                        </div>
                    ) : (
                        activities.map((activity) => {
                            const updates = activity.updates || [];
                            const latestUpdate = updates.length > 0 ? updates[0] : null; // Already sorted latest()
                            const myUpdate = updates.find(u => u.user_id === currentUserId);
                            
                            // Determine overall status colors
                            let statusColor = "bg-card border-border";
                            if (latestUpdate) {
                                if (latestUpdate.status === 'done') statusColor = "bg-green-50/50 border-green-200 dark:bg-green-950/20 dark:border-green-900";
                                else if (latestUpdate.status === 'in_progress') statusColor = "bg-yellow-50/50 border-yellow-200 dark:bg-yellow-950/20 dark:border-yellow-900";
                            }

                            return (
                                <Card key={activity.id} className={`rounded-xl shadow-sm transition-colors ${statusColor}`}>
                                    <div className="p-5 flex flex-col md:flex-row gap-6">
                                        
                                        {/* Activity Info */}
                                        <div className="md:w-1/3 flex flex-col gap-2">
                                            <div className="flex items-start justify-between">
                                                <h3 className="font-semibold text-lg leading-tight">{activity.title}</h3>
                                            </div>
                                            {activity.category && (
                                                <div className="self-start px-2 py-0.5 bg-primary/10 text-primary text-xs rounded font-medium">
                                                    {activity.category}
                                                </div>
                                            )}
                                            {activity.description && (
                                                <p className="text-sm text-muted-foreground mt-1">
                                                    {activity.description}
                                                </p>
                                            )}
                                        </div>

                                        {/* Updates / Handover Log */}
                                        <div className="md:w-2/3 flex flex-col gap-4">
                                            {/* History Log */}
                                            {updates.length > 0 ? (
                                                <div className="flex flex-col gap-2 bg-background/50 rounded-lg p-3 border">
                                                    <h4 className="text-xs uppercase tracking-wider text-muted-foreground font-semibold mb-1">Today's Handover Log</h4>
                                                    {updates.map(update => (
                                                        <div key={update.id} className="flex gap-3 text-sm">
                                                            <div className="shrink-0 mt-0.5">
                                                                {update.status === 'done' ? (
                                                                    <CheckCircle className="w-4 h-4 text-green-500" />
                                                                ) : update.status === 'in_progress' ? (
                                                                    <Clock className="w-4 h-4 text-yellow-500" />
                                                                ) : (
                                                                    <AlertCircle className="w-4 h-4 text-muted-foreground" />
                                                                )}
                                                            </div>
                                                            <div className="flex flex-col">
                                                                <div className="flex items-center gap-2">
                                                                    <span className="font-medium">{update.user?.name}</span>
                                                                    <span className="text-muted-foreground text-xs">
                                                                        {new Date(update.created_at).toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'})}
                                                                    </span>
                                                                </div>
                                                                {update.remark && (
                                                                    <span className="text-muted-foreground">{update.remark}</span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            ) : (
                                                <div className="flex items-center p-3 rounded-lg border bg-background/50 text-sm text-muted-foreground">
                                                    <AlertCircle className="w-4 h-4 mr-2" />
                                                    No updates logged for this activity today.
                                                </div>
                                            )}

                                            {/* Action Form (Only if today or past, mostly you update today's activities) */}
                                            <div className="mt-auto pt-2 border-t">
                                                <ActivityUpdateForm 
                                                    activityId={activity.id}
                                                    activityDate={currentDate}
                                                    currentStatus={myUpdate?.status || 'pending'}
                                                    currentRemark={myUpdate?.remark}
                                                />
                                            </div>
                                        </div>

                                    </div>
                                </Card>
                            );
                        })
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
