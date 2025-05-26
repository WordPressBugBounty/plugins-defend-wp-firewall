<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="dfwp-init-setup-wrapper" class="relative z-10" aria-labelledby="modal-title" role="dialog" aria-modal="true">
	<div class="fixed inset-0 z-10 w-screen overflow-y-auto">
		<div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
			<div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
				<div>
					<div>
						<div class="dfwp-success">
							<div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100 ">
								<svg class="h-6 w-6 text-green-600 " fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
									<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"></path>
								</svg>
							</div>
						</div>
						<div class="dfwp-error">
							<div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 ">
							<svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
								<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
							</svg>
							</div>
						</div>
						<div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full  dfwp-loading">
							<svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
								<path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
							</svg>
						</div>
						<div class="mt-3 text-center sm:mt-5">
							<h3 class="text-base font-semibold leading-6 text-gray-900 dfwp-loading" id="modal-title">Your website is connecting. Please wait...</h3>
							<h3 class="text-base font-semibold leading-6 text-gray-900 dfwp-success" id="modal-title">Your website is now protected!</h3>
							<h3 class="text-base font-semibold leading-6 text-gray-900 dfwp-error" id="modal-title">Unable to connect!</h3>
							<div class="mt-2">
								<p class="text-sm text-gray-500 dfwp-success">We will scan for vulnerabilities in your website, automatically patch them and notify you.</p>
								<p class="text-sm text-gray-500 dfwp-error">
									<span id='dfwp-error-res'></span>
									<span id='dfwp-error-msg'></span>
								</p>
							</div>
						</div>
					</div>
					<div class="dfwp-error">
						<div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3 flex">
							<a href="mailto:help@defendwp.org" type="button" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:col-start-2">Contact us</a>
							<button type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0" onClick="defend_wp_firewall_refresh_page()">Connect again</button>
						</div>
					</div>
					<div class="dfwp-success dfwp-mail-wrapper">
						<hr style="margin: 30px 0 20px;">
						<h4 class="text-base font-semibold leading-6 text-gray-900 text-sm text-center ">Join our mailing list</h4>
						<div class="mt-2 max-w-xl text-xs text-gray-500 text-center ">
							<p>Get occasional emails of latest product updates and other important information</p>
						</div>
						<form class="mt-5 sm:flex sm:items-center justify-center">
							<div class="w-full sm:max-w-xs">
								<label for="email" class="sr-only">Email</label>
								<input type="email" name="email" id="dfwp_join_email" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="you@example.com">
							</div>
							<button type="submit" class="mt-3 inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:ml-3 sm:mt-0 sm:w-auto" id="dfwp_join">Join</button>
						</form>
						<div class="text-center mt-2">
							<p class="dfwp-join-error text-sm  text-red-600">
							<p class="dfwp-join-res text-sm  text-red-600">
							</p>
						</div>
						<div class="mt-3 text-sm leading-6 sm:flex sm:items-center justify-center">
							<a href="<?php echo esc_url( DEFEND_WP_FIREWALL_LATER_URL ); ?>" class="font-semibold text-indigo-600 hover:text-indigo-500">
								I'll do this later
								<span aria-hidden="true"> &rarr;</span>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
