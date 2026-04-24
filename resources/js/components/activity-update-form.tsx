import { useForm, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { FormEventHandler, useState } from 'react';
import { LoaderCircle, CheckCircle, Clock } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';

interface ActivityUpdateFormProps {
    activityId: number;
    activityDate: string;
    currentStatus?: string;
    currentRemark?: string | null;
}

export function ActivityUpdateForm({ 
    activityId, 
    activityDate, 
    currentStatus = 'pending', 
    currentRemark = '' 
}: ActivityUpdateFormProps) {
    const { data, setData, post, processing, errors, recentlySuccessful } = useForm({
        activity_id: activityId,
        activity_date: activityDate,
        status: currentStatus,
        remark: currentRemark || '',
    });

    const [isEditing, setIsEditing] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('activity_updates.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setIsEditing(false);
                // The router automatically updates the flash data which implies success
            }
        });
    };

    if (!isEditing && currentStatus !== 'pending') {
        return (
            <div className="flex flex-col gap-2 p-3 bg-muted/30 rounded-lg border">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        {currentStatus === 'done' ? (
                            <Badge variant="default" className="bg-green-600 hover:bg-green-700">
                                <CheckCircle className="w-3 h-3 mr-1" /> Done
                            </Badge>
                        ) : (
                            <Badge variant="secondary" className="bg-yellow-500/20 text-yellow-700 dark:text-yellow-400">
                                <Clock className="w-3 h-3 mr-1" /> In Progress
                            </Badge>
                        )}
                        <span className="text-sm text-muted-foreground ml-2">Status via you</span>
                    </div>
                    <Button variant="ghost" size="sm" onClick={() => setIsEditing(true)}>
                        Update
                    </Button>
                </div>
                {currentRemark && (
                    <div className="text-sm border-l-2 pl-3 mt-1 text-muted-foreground italic">
                        "{currentRemark}"
                    </div>
                )}
            </div>
        );
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-3 p-3 bg-card border shadow-sm rounded-lg">
            <div className="flex items-start gap-4">
                <div className="w-32 shrink-0">
                    <Select
                        value={data.status}
                        onValueChange={(value) => setData('status', value)}
                        disabled={processing}
                    >
                        <SelectTrigger className="h-9">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="in_progress">In Progress</SelectItem>
                            <SelectItem value="done">Done</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                
                <div className="grow">
                    <Input
                        type="text"
                        placeholder="Add a remark (optional)..."
                        className="h-9 w-full"
                        value={data.remark}
                        onChange={(e) => setData('remark', e.target.value)}
                        disabled={processing}
                    />
                </div>

                <div className="shrink-0 flex items-center gap-2">
                    {isEditing && (
                        <Button 
                            type="button" 
                            variant="ghost" 
                            size="sm" 
                            className="h-9"
                            onClick={() => {
                                setData({
                                    activity_id: activityId,
                                    activity_date: activityDate,
                                    status: currentStatus,
                                    remark: currentRemark || ''
                                });
                                setIsEditing(false);
                            }}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                    )}
                    <Button type="submit" size="sm" className="h-9" disabled={processing || data.status === 'pending'}>
                        {processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
                        Save Update
                    </Button>
                </div>
            </div>
            
            <InputError message={errors.status} />
            <InputError message={errors.remark} />
            <InputError message={errors.activity_id} />
            <InputError message={errors.activity_date} />
            
            {recentlySuccessful && (
                <div className="text-sm text-green-600 font-medium">Saved.</div>
            )}
        </form>
    );
}
