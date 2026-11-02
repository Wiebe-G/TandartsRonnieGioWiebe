import { cn } from '@/lib/utils';

// Full logo with text; the light-text version takes over in dark mode
export default function AppLogoFull({ className }: { className?: string }) {
	return (
		<>
			<img
				src="/logo.png"
				alt="Tandartspraktijk Hengelo"
				className={cn('object-contain dark:hidden', className)}
			/>
			<img
				src="/logo-dark.png"
				alt="Tandartspraktijk Hengelo"
				className={cn('hidden object-contain dark:block', className)}
			/>
		</>
	);
}
