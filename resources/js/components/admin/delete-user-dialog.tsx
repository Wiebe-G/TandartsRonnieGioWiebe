import { Form } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
	Dialog,
	DialogClose,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogTitle,
	DialogTrigger,
} from '@/components/ui/dialog';

type Props = {
	form: { action: string; method: 'get' | 'post' };
	name: string;
	/** Render a small icon button (for table rows) instead of a full button. */
	compact?: boolean;
};

export default function DeleteUserDialog({
	form,
	name,
	compact = false,
}: Props) {
	const [open, setOpen] = useState(false);

	return (
		<Dialog open={open} onOpenChange={setOpen}>
			<DialogTrigger asChild>
				{compact ? (
					<Button
						variant="ghost"
						size="icon"
						aria-label={`${name} verwijderen`}
					>
						<Trash2 className="text-destructive" />
					</Button>
				) : (
					<Button variant="destructive">
						<Trash2 />
						Verwijderen
					</Button>
				)}
			</DialogTrigger>
			<DialogContent>
				<DialogTitle>{name} verwijderen?</DialogTitle>
				<DialogDescription>
					Dit account wordt definitief verwijderd. Dit kan niet
					ongedaan worden gemaakt.
				</DialogDescription>

				<Form
					{...form}
					options={{ preserveScroll: true }}
					onFinish={() => setOpen(false)}
				>
					{({ processing }) => (
						<DialogFooter className="gap-2">
							<DialogClose asChild>
								<Button variant="secondary" type="button">
									Annuleren
								</Button>
							</DialogClose>
							<Button
								variant="destructive"
								type="submit"
								disabled={processing}
							>
								Verwijderen
							</Button>
						</DialogFooter>
					)}
				</Form>
			</DialogContent>
		</Dialog>
	);
}
