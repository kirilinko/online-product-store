const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;

import { ReactComponent as CategoryIcon } from "../../assets/icons/category_icon.svg";
import categoryIconUrl from "../../assets/icons/category_icon.svg";

registerBlockType("fast-simon/category", {
	title: __("Category", "instantsearch-for-woocommerce"),
	icon: { src: CategoryIcon },
	category: "fast-simon",
	supports: {
		align: [ 'wide', 'full' ]
	},
	edit: props => {

		console.info(props);

		const { className } = props;

		return (
			<div className={`${className} fast-simon-block fast-simon-category`} >

				<div className={"block-title-container"}>
					<figure className="logo">
						<img src={categoryIconUrl} alt="category icon" width="40" height="40"/>
					</figure>
					<p className="block-title">Fast Simon's Category Block</p>
				</div>

				<p className="block-info">
					Enter the Fast Simon Dashboard to edit configurations via the admin menu.
				</p>

			</div>
		);
	},

	save(props) {
		return null;
	}
});
