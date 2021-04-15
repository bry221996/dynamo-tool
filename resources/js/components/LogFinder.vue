<template>
	<div class="container mt-1">
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
							</div>
							<div class="row mt-2">
								<button
									class="btn btn-primary px-5 btn-sm"
									:disabled="buttonIsDisabled"
									@click.prevent="find"
									v-html="btnDisplay"
								></button>
							</div>
						</form>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-3">
								<div style="max-height: 80vh; overflow: scroll">
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
							<div class="col-9">
								<div class="tab-content" id="nav-tabContent">
									<div
										v-for="log in logs"
										:key="log.uuid"
										class="tab-pane fade"
										:id="`list-${log.uuid}`"
										role="tabpanel"
										:aria-labelledby="`list-${log.uuid}-list`"
									>
										<ul class="list-group">
											<li
												class="py-2 list-group-item d-flex justify-content-between align-items-center"
											>
												<code>Mobile: {{ log.mobile }}</code>
											</li>
											<li
												class="py-2 list-group-item d-flex justify-content-between align-items-center"
											>
												<code>Timestamp: {{ log.created_at }}</code>
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
													<code>Request</code>
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

export default {
	name: "LogFinder",
	components: {
		VueJsonPretty,
	},
	data() {
		return {
			mobile: null,
			queryDate: "2021-04-16",
			method: "",
			logs: [],
			isProcessing: false,
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

			return obj;
		},
		btnDisplay() {
			return this.isProcessing
				? `<span class="spinner-grow spinner-grow-sm mr-2" role="status" aria-hidden="true"></span>Finding...`
				: "Find";
		},
	},
	methods: {
		find() {
			this.isProcessing = true;
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
