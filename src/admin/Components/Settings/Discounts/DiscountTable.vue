<template>
	<div v-if="discounts.length" class="wpf_email_notifications">
		<el-table :data="discounts">
			<el-table-column type="index" width="50" />
			<el-table-column width="120" label="Status">
				<template slot-scope="scope">
					<el-switch
						:disabled="!scope.row.id"
						v-model="scope.row.status"
						@change="emitClick('saveDiscount', scope.row)"
						active-color="#13ce66"
						active-value="active"
					>
					</el-switch>
				</template>
			</el-table-column>
			<el-table-column property="title" width="190" label="Title">
			</el-table-column>
			<el-table-column property="discount_code" label="Discount code">
			</el-table-column>
			<el-table-column property="type" width="180" label="Discount Type">
			</el-table-column>
			<el-table-column property="discount_code" label="Discount code">
			</el-table-column>
			<el-table-column width="160" label="">
				<template slot-scope="scope">
					<el-button-group>
						<el-button
							@click="emitClick('editDiscount', scope.$index)"
							type="success"
							size="mini"
							icon="el-icon-edit"
						></el-button>
						<el-button
							@click="emitClick('deleteDiscount', [scope.row.id, scope.$index])"
							type="danger"
							size="mini"
							icon="el-icon-delete"
						></el-button>
					</el-button-group>

				</template>
			</el-table-column>
		</el-table>
	</div>
	<div v-else>
		<h3 class="text-center">No discounts configured yet.</h3>
	</div>
</template>
<script type="text/babel">
	export default {
	    name: 'discounts_table',
	    props: ['discounts'],
	    methods: {
	        emitClick(eventName, dataIndex) {
	            this.$emit(eventName, dataIndex);
	        }
	    }
	}
</script>
