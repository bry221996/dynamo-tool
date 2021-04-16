<template>
	<div class="container-fluid mt-1">
		<div class="row">
			<div class="col">
				<div class="card">
					<div class="card-header">
						<form>
							<div class="row">
								<div class="col px-0">
									<input
										class="form-control form-control-sm"
										type="date"
										v-model="queryDate"
									/>
								</div>
								<div class="col px-0">
									<input
										class="form-control form-control-sm"
										type="text"
										placeholder="mobile"
										v-model="mobile"
									/>
								</div>
								<div class="col px-0">
									<select v-model="method" class="form-control form-control-sm">
										<option value="">HTTP Method</option>
										<option value="GET">GET</option>
										<option value="POST">POST</option>
										<option value="PUT">PUT</option>
									</select>
								</div>
								<div class="col px-0">
									<select
										v-model="transaction"
										class="form-control form-control-sm"
									>
										<option value="">Transaction Type</option>
										<option
											v-for="transactionType in transactionTypes"
											:key="transactionType.value"
											:value="transactionType.value"
										>
											{{ transactionType.display }}
										</option>
									</select>
								</div>
							</div>
							<div class="row mt-2">
								<button
									class="btn btn-primary px-5 btn-sm mr-2"
									:disabled="buttonIsDisabled"
									@click.prevent="find"
									v-html="btnDisplay"
								></button>
								<button
									class="btn btn-primary px-5 btn-sm"
									@click.prevent="reset"
								>
									Reset
								</button>
							</div>
						</form>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-3" style="max-height: 80vh; overflow: scroll">
								<div>
									<div class="list-group" id="list-tab" role="tablist">
										<a
											v-for="log in logs"
											:key="log.uuid"
											class="list-group-item list-group-item-action py-2"
											:id="`list-${log.uuid}-list`"
											data-toggle="list"
											:href="`#list-${log.uuid}`"
											role="tab"
											:aria-controls="log.uuid"
										>
											<code>{{ log.uuid }}</code>
										</a>
									</div>
								</div>
							</div>
							<div class="col-9" style="max-height: 80vh; overflow: scroll">
								<div class="tab-content" id="nav-tabContent">
									<div
										v-for="log in logs"
										:key="log.uuid"
										class="tab-pane fade"
										:id="`list-${log.uuid}`"
										role="tabpanel"
										:aria-labelledby="`list-${log.uuid}-list`"
									>
										<button
                                            class="btn btn-sm mb-2"
											type="button"
											v-clipboard:copy="JSON.stringify(log, null, 2)"
										>
											Copy!
										</button>
										<ul class="list-group">
											<li
												class="py-2 list-group-item d-flex justify-content-between align-items-center"
											>
												<code>{{ getIdentifierDisplay(log) }}</code>
											</li>
											<li
												class="py-2 list-group-item d-flex justify-content-between align-items-center"
											>
												<code>Timestamp: {{ formatDate(log.created_at) }}</code>
											</li>
											<li
												class="py-2 list-group-item d-flex justify-content-between align-items-center"
											>
												<code>Transaction: {{ log.transaction_type }}</code>
											</li>
											<li
												class="py-2 list-group-item d-flex justify-content-between align-items-center"
											>
												<code>Response Code: {{ log.millipede_error }}</code>
											</li>
											<li
												class="py-2 list-group-item d-flex justify-content-between align-items-center"
											>
												<code>Response Message: {{ log.message }}</code>
											</li>
											<li class="py-2 list-group-item d-flex">
												<div class="mr-4">
													<code>Request</code>
												</div>

												<vue-json-pretty :path="'res'" :data="log.request">
												</vue-json-pretty>
											</li>
											<li class="py-2 list-group-item d-flex">
												<div class="mr-4">
													<code>Response</code>
												</div>
												<vue-json-pretty
													:path="'res'"
													:data="log.error_response"
												>
												</vue-json-pretty>
											</li>
										</ul>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import VueJsonPretty from "vue-json-pretty";
import "vue-json-pretty/lib/styles.css";
import axios from "axios";
import moment from "moment";

export default {
	name: "LogFinder",
	components: {
		VueJsonPretty,
	},
	data() {
		return {
			mobile: "",
			queryDate: "2021-04-16",
			method: "",
			logs: [],
			isProcessing: false,
			transaction: "",
			transactionTypes: [
				{
					display: "Mobile subscriber attrs. (UUP)",
					value: "subscribers",
				},
				{
					display: "Get Points",
					value: "points",
				},
				{
					display: "PAC",
					value: "purchases",
				},
				{
					display: "Get Profile",
					value: "profiles",
				},
				{
					display: "Update Profile",
					value: "profiles/",
				},
				{
					display: "Redemptions",
					value: "rewards/redemptions",
				},
			],
		};
	},
	computed: {
		buttonIsDisabled() {
			return !(this.mobile && this.queryDate) || this.isProcessing;
		},
		params() {
			let obj = {
				date: this.queryDate,
				mobile: this.mobile,
			};

			if (this.method) {
				obj.method = this.method;
			}

			if (this.transaction) {
				obj.transaction = this.transaction;
			}

			return obj;
		},
		btnDisplay() {
			return this.isProcessing
				? `<span class="spinner-grow spinner-grow-sm mr-2" role="status" aria-hidden="true"></span>Finding...`
				: "Find";
		},
	},
	methods: {
		getIdentifierDisplay(log) {
			if (log.account_number) {
				return `Account Number: ${log.account_number}`;
			}

			return `Mobile Number: ${log.mobile}`;
		},
		formatDate(date) {
			return moment(date);
		},
		reset() {
			this.mobile = "";
			this.method = "";
			this.transaction = "";
			this.logs = [];
		},
		find() {
			this.isProcessing = true;
			this.logs = [];
			axios
				.get("/logs", {
					params: this.params,
				})
				.then((res) => {
					this.logs = res.data.data;
				})
				.finally(() => {
					this.isProcessing = false;
				});
		},
	},
};
</script>

<style>
.vjs-key,
.vjs-tree,
code {
	font-size: 12px !important;
}
</style>
