import AppLogoFull from '@/components/app-logo-full';
import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
	return (
		<>
			{/* Collapsed sidebar only has room for the tooth */}
			<AppLogoIcon className="hidden size-8 group-data-[collapsible=icon]:block" />
			<div className="group-data-[collapsible=icon]:hidden">
				<AppLogoFull className="h-14 w-auto" />
			</div>
		</>
	);
}
